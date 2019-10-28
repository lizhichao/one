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
     * 60秒内无请求 将逐渐释放连接
     * @var int
     */
    private $free_time = 60;

    private static $last_use_time = 0;


    private function getTsId()
    {
        if ($this->transaction_id) {
            return $this->transaction_id;
        } else {
            return get_co_id();
        }
    }

    /**
     * push对象进入连接池
     * @param $obj
     * @param bool $s
     */
    public function push($obj, $s = false)
    {
        if (_CLI_) {
            $id = $this->key . '_' . $this->getTsId();
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
            $co_id = $key . '_' . $this->getTsId();
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
        $time = time();
        if (!isset(static::$pools[$key])) {
            static::$pools[$key] = new Channel($this->config['max_connect_count']);
        }
        $sp = static::$pools[$key];

        if ($sp->isEmpty()) {
            if (self::$connect_count < $this->config['max_connect_count']) {
                self::$connect_count++;
                $rs = $this->createRes();
                $rs->create_time = time();
                $sp->push($rs);
            }
        } else if (self::$last_use_time > 0 && (self::$last_use_time + $this->free_time) < $time && $sp->length() > 1) {
            $sp->pop();
            self::$connect_count--;
            if(isset($this->config['free_call'])){
                $this->config['free_call']->call($this);
            }
        }
        self::$last_use_time = $time;
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