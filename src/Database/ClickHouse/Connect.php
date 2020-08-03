<?php

namespace One\Database\ClickHouse;

use One\ConfigTrait;
use One\Facades\Log;
use One\Swoole\Pools;

class Connect
{

    use ConfigTrait, Pools;

    private $key;

    private $config = [];

    /**
     * @var string
     */
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

    private function debugLog($sql, $time = 0, $build = [], $err = [])
    {
        if (self::$conf['debug_log']) {
            $time = $time ? (microtime(true) - $time) * 1000 : $time;
            if (is_string($sql)) {
                $sql1 = str_replace('%', "```", $sql);
                $s    = vsprintf(str_replace('?', "'%s'", $sql1), $build);
                $s    = str_replace('```', "%", $s);
                $id   = md5(str_replace('()', '', str_replace(['?', ','], '', $sql)));
            } else {
                $s  = $sql;
                $id = $sql[0];
            }
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


    /**
     * @param string $sql
     * @param array $data
     * @return mixed
     */
    public function select($sql, $data = [])
    {
        $this->debugLog($sql, 0, $data);
        $ck  = $this->pop();
        $res = $ck->select($sql, $data);
        $this->push($ck);
        foreach ($res as $i => $arr) {
            $res[$i] = array_to_object($arr, $this->model);
        }
        return $res;
    }

    /**
     * @param string $table
     * @param array $fields
     * @param array $data
     */
    public function insert($table, $fields, $data)
    {
        $this->debugLog([$table, $fields, $data]);
        $ck = $this->pop();
        $ck->insert($table, $fields, $data);
        $this->push($ck);
    }

    /**
     * @param string $sql
     * @param int
     */
    public function exec($sql, $data = [])
    {
        $this->debugLog($sql, 0, $data);
        $ck = $this->pop();
        $ck->execute($sql, $data);
        $this->push($ck);
    }

    /**
     * @return \SeasClick
     * @throws ClickHouseException
     */
    private function createRes()
    {
        try {
            return new \SeasClick($this->config);
        } catch (\PDOException $e) {
            throw new ClickHouseException('connection failed ' . $e->getMessage(), $e->getCode());
        }
    }

}