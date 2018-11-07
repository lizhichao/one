<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/30
 * Time: 10:49
 */

namespace One\Swoole;


use Swoole\Coroutine\Channel;

trait Pools
{

    private static $pools = [];

    private static $connect_count = 0;

    private static $sw = [];


    /**
     * push对象进入连接池
     * @param $obj
     * @param bool $s
     */
    public function push($obj, $s = false)
    {
        if (_CLI_) {
            $id = $this->key . '_' . get_co_id();
            if (isset(self::$sw[$id])) {
                if ($s || $obj !== self::$sw[$id]) {
                    unset(self::$sw[$id]);
                    static::$pools[$this->key]->push($obj);
                }
            } else {
                static::$pools[$this->key]->push($obj);
            }
        }
    }

    /**
     * @param bool $sw 是否事物
     * @return \PDO | \Redis
     */
    public function pop($sw = false)
    {
        $key = $this->key;
        if (_CLI_) {
            $co_id = $key . '_' . get_co_id();
            if (isset(self::$sw[$co_id])) {
                return self::$sw[$co_id];
            }
            $rs = $this->getCliRes($key);
            if ($sw) {
                self::$sw[$co_id] = $rs;
            }
            return $rs;
        } else {
            return $this->getFpmRes($key);
        }
    }

    private function getCliRes($key)
    {
        if (!isset(static::$pools[$key])) {
            static::$pools[$key] = new Channel($this->config['max_connect_count']);
        }
        $sp = static::$pools[$key];

        if ($sp->isEmpty() && self::$connect_count < $this->config['max_connect_count']) {
            self::$connect_count++;
            $sp->push($this->createRes());
        }
        return $sp->pop();
    }


    private function getFpmRes($key)
    {
        if (!isset(static::$pools[$key])) {
            static::$pools[$key] = $this->createRes();
        }
        return static::$pools[$key];
    }

}