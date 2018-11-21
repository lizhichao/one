<?php

namespace One\Database\Mysql;

use One\ConfigTrait;
use One\Facades\Log;
use One\Swoole\Pools;
use Swoole\Mysql\Exception;

class Connect
{

    use ConfigTrait, Pools;

    private static $pdo = [];

    private $key;

    private $config = [];

    private $model;

    /**
     * Connect constructor.
     * @param string $key
     * @param string $model
     */
    public function __construct($key, $model)
    {
        $this->key    = $key;
        $this->model  = $model;
        $this->config = self::$conf[$key];
    }

    /**
     * @param string $sql
     * @param array $data
     * @return \PDOStatement|array
     */
    private function execute($sql, $data = [], $retry = 0, $return_pdo = false)
    {
        $pdo  = $this->pop();
        $time = microtime(true);
        try {
            $res = $pdo->prepare($sql, [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]);
            if (!$res) {
                return $this->retry($sql, $time, $data, $pdo->errorInfo()[2], $retry, $return_pdo);
            }
            $res->setFetchMode(\PDO::FETCH_CLASS, $this->model);
            $res->execute($data);
            $this->debugLog($sql, $time, $data, '');
            if ($return_pdo) {
                return [$res, $pdo];
            } else {
                $this->push($pdo);
                return $res;
            }
        } catch (\PDOException $e) {
            $this->debugLog($sql, $time, $data, $e->getMessage());
            return $this->retry($sql, $time, $data, $e->getMessage(), $retry, $return_pdo);
        } catch (\Throwable $e) {
            self::$connect_count--;
            throw new DbException(json_encode(['info' => $e->getMessage(), 'sql' => $sql]), 7);
        }
    }

    private function retry($sql, $time, $data, $err, $retry, $return_pdo)
    {
        self::$connect_count--;
        $this->debugLog($sql, $time, $data, $err);
        if ($this->isBreak($err) && $retry < 3) {
            return $this->execute($sql, $data, ++$retry, $return_pdo);
        }
        throw new DbException(json_encode(['info' => $err, 'sql' => $sql]), 7);
    }

    private function debugLog($sql, $time = 0, $build = [], $err = [])
    {
        if (self::$conf['debug_log']) {
            $time = $time ? (microtime(true) - $time) * 1000 : $time;
            $info = explode('?', $sql);
            foreach ($info as $i => &$v) {
                if (isset($build[$i])) {
                    $v = $v . "'{$build[$i]}'";
                }
            }
            $s   = implode('', $info);
            $sql = str_replace(['?', ','], '', $sql);
            $id  = md5(str_replace('()', '', $sql));

            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 13);
            foreach ($trace as $i => $v) {
                if (strpos($v['file'], DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'lizhichao' . DIRECTORY_SEPARATOR) === false) {
                    $k = $i + 1;
                    break;
                }
            }
            Log::debug(['sql' => $s, 'id' => $id, 'time' => $time, 'err' => $err], $k, 'sql');
        }
    }

    public function getDns()
    {
        return $this->config['dns'];
    }


    private function isBreak($error)
    {
        $info = [
            'server has gone away',
            'no connection to the server',
            'Lost connection',
            'is dead or not enabled',
            'Error while sending',
            'decryption failed or bad record mac',
            'server closed the connection unexpectedly',
            'SSL connection has been closed unexpectedly',
            'Error writing data to the connection',
            'Resource deadlock avoided',
        ];

        foreach ($info as $msg) {
            if (false !== stripos($error, $msg)) {
                return true;
            }
        }
        return false;
    }


    private $log_k = 0;


    /**
     * @param string $sql
     * @param array $data
     * @return mixed
     */
    public function find($sql, $data = [])
    {
        return $this->execute($sql, $data)->fetch();
    }

    /**
     * @param string $sql
     * @param array $data
     * @return mixed
     */
    public function findAll($sql, $data = [])
    {
        return $this->execute($sql, $data)->fetchAll();
    }

    /**
     * @param string $sql
     * @param int
     */
    public function exec($sql, $data = [], $last_id = false)
    {
        list($res, $pdo) = $this->execute($sql, $data, 0, true);
        if ($last_id) {
            $r = $pdo->lastInsertId();
        } else {
            $r = $res->rowCount();
        }
        $this->push($pdo);
        return $r;
    }


    /**
     * @return bool
     */
    public function beginTransaction()
    {
        if ($this->inTransaction()) {
            return true;
        }
        $this->debugLog('begin');
        return $this->pop(true)->beginTransaction();
    }

    /**
     * @return bool
     */
    public function rollBack()
    {
        if ($this->inTransaction()) {
            $this->debugLog('rollBack');
            $pdo = $this->pop();
            $r   = $pdo->rollBack();
            $this->push($pdo, true);
            return $r;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function commit()
    {
        if ($this->inTransaction()) {
            $this->debugLog('commit');
            $pdo = $this->pop();
            $r   = $pdo->commit();
            $this->push($pdo, true);
            return $r;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function inTransaction()
    {
        $pdo = $this->pop();
        $r   = $pdo->inTransaction();
        if (!$r) {
            $this->push($pdo);
        }
        return $r;
    }

    /**
     * @return \PDO
     * @throws DbException
     */
    private function createRes()
    {
        try {
            return new \PDO($this->config['dns'], $this->config['username'], $this->config['password'], $this->config['ops']);
        } catch (\PDOException $e) {
            throw new DbException('connection failed ' . $e->getMessage(), 0);
        }
    }

}