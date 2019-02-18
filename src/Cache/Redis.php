<?php

namespace One\Cache;

use One\ConfigTrait;
use One\Facades\Log;
use One\Swoole\Pools;

/**
 * Class Redis
 * @package One\Cache
 * @mixin \Redis
 */
class Redis extends Cache
{
    use ConfigTrait, Pools;

    protected $key = '';

    private $config = [];

    private $retry_count = 3;

    public function __construct($key = 'default')
    {
        $this->setConnection($key);
    }

    public function __call($name, $arguments)
    {
        $rs = $this->pop();
        try {
            $ret = $rs->$name(...$arguments);
            $this->push($rs);
            $this->setRetryCount();
            return $ret;
        } catch (\RedisException $e) {
            return $this->retry($name, $arguments, $e->getMessage(), $e->getCode());
        }
    }

    private function setRetryCount()
    {
        $this->retry_count = $this->config['max_connect_count'] + 1;
    }

    private function retry($name, $arguments, $msg, $code)
    {
        Log::warn('retry ' . $name);
        self::$connect_count--;
        if ($this->retry_count > 0) {
            $this->retry_count--;
            return $this->{$name}(...$arguments);
        } else {
            $this->setRetryCount();
            throw new \Exception($msg, $code);
        }
    }


    public function setConnection($key)
    {
        $this->key    = $key;
        $this->config = self::$conf[$key];
        return $this;
    }

    /**
     * @return \Redis
     */
    private function createRes()
    {
        $r = new \Redis();
        $r->connect(self::$conf[$this->key]['host'], self::$conf[$this->key]['port'], 0);
        return $r;
    }


    public function get($key, \Closure $closure = null, $ttl = null, $tags = [])
    {
        try {
            $rs  = $this->pop();
            $val = $rs->get($this->getTagKey($key, $tags));
            if ((!$val) && $closure) {
                $val = $closure();
                $this->set($key, $val, $ttl, $tags);
            } else if ($val) {
                $val = unserialize($val);
            }
            $this->push($rs);
            $this->setRetryCount();
            return $val;
        } catch (\RedisException $e) {
            return $this->retry('get', func_get_args(), $e->getMessage(), $e->getCode());
        }
    }

    public function del($key)
    {
        try {
            if (is_string($key)) {
                $key = self::$conf['prefix'] . $key;
            }
            $rs  = $this->pop();
            $ret = $rs->del($key);
            $this->push($rs);
            $this->setRetryCount();
            return $ret;
        } catch (\RedisException $e) {
            return $this->retry('del', func_get_args(), $e->getMessage(), $e->getCode());
        }

    }

    public function delRegex($key)
    {
        return $this->del($this->keys($key));
    }

    public function flush($tag)
    {
        $id = md5(uuid());
        $this->set($tag, $id);
        return $id;
    }

    public function set($key, $val, $ttl = null, $tags = [])
    {
        try {
            $rs  = $this->pop();
            $ret = $rs->set($this->getTagKey($key, $tags), serialize($val), $ttl);
            $this->push($rs);
            $this->setRetryCount();
            return $ret;
        } catch (\RedisException $e) {
            return $this->retry('set', func_get_args(), $e->getMessage(), $e->getCode());
        }

    }

}
