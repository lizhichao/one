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

    private $transaction_id = null;

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

    public function transactionId($id)
    {
        $this->transaction_id = $id;
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
            if ($res->errorInfo()[0] !== '00000') {
                $this->push($pdo);
                throw new DbException(json_encode(['info' => $res->errorInfo(), 'sql' => $sql, 'data' => $data]), 8);
            }
            if ($return_pdo) {
                return [$res, $pdo];
            } else {
                $this->push($pdo);
                return $res;
            }
        } catch (\PDOException $e) {
            $this->debugLog($sql, $time, $data, $e->getMessage());
            return $this->retry($sql, $time, $data, $e->getMessage(), $retry, $return_pdo);
        } catch (DbException $e) {
            throw $e;
        } catch (\Throwable $e) {
            self::$connect_count--;
            throw new DbException(json_encode(['info' => $e->getMessage(), 'sql' => $sql]), $e->getCode());
        }
    }

    private function retry($sql, $time, $data, $err, $retry, $return_pdo)
    {
        self::$connect_count--;
        $this->debugLog($sql, $time, $data, $err);
        if ($this->isBreak($err) && $retry < ($this->config['max_connect_count'] + 1)) {
            if (_CLI_ === false) {
                unset(static::$pools[$this->key]);
            }
            $co_id = $this->key . '_' . $this->getTsId();
            if (isset(self::$sw[$co_id]) === false) {
                return $this->execute($sql, $data, ++$retry, $return_pdo);
            } else {
                unset(self::$sw[$co_id]);
            }
        }
        throw new DbException(json_encode(['info' => $err, 'sql' => $sql]), 7);
    }

    private function debugLog($sql, $time = 0, $build = [], $err = [])
    {
        if (self::$conf['debug_log']) {
            $time  = $time ? (microtime(true) - $time) * 1000 : $time;
            $sql1  = str_replace('%', "```", $sql);
            $s     = vsprintf(str_replace('?', "'%s'", $sql1), $build);
            $s     = str_replace('```', "%", $s);
            $id    = md5(str_replace('()', '', str_replace(['?', ','], '', $sql)));
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 13);
            $k     = 1;
            foreach ($trace as $i => $v) {
                if (strpos($v['file'], DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'lizhichao' . DIRECTORY_SEPARATOR) === false) {
                    $k = $i + 1;
                    break;
                }
            }
            Log::debug(['sql' => $s, 'id' => $id, 'key' => $this->key, 'time' => $time, 'err' => $err], $k, 'sql');
        }
    }

    public function getDns()
    {
        return $this->config['dns'];
    }

    public function getKey()
    {
        return $this->key;
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
        $rc = 0;
        retry:
        {
            if ($this->inTransaction()) {
                return true;
            }
            $r = $this->pop(true)->beginTransaction();
        }
        if ($r === false) {
            self::$connect_count--;
            $co_id = $this->key . '_' . $this->getTsId();
            if (isset(self::$sw[$co_id])) {
                unset(self::$sw[$co_id]);
            }
            if ($rc < $this->config['max_connect_count'] + 1) {
                $rc++;
                echo "beginTransaction retry {$rc}\n";
                goto retry;
            }
        }
        $this->debugLog('begin');
        if ($r === false) {
            throw new DbException('begin transaction fail', 9);
        }
        return $r;
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
            throw new DbException('connection failed ' . $e->getMessage(), $e->getCode());
        }
    }

}