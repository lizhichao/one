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

    private $transaction_id = null;

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
            if (_CLI_ === false) {
                unset(static::$pools[$this->key]);
            }
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
        if (isset($this->config['is_cluster']) && $this->config['is_cluster'] === true) {
            return new \RedisCluster(...$this->config['args']);
        } else {
            $r = new \Redis();
            $r->connect($this->config['host'], $this->config['port'], 0);
            if (!empty($this->config['auth'])) {
                $r->auth($this->config['auth']);
            }
            if ($this->config['prefix'] !== '') {
                $r->setOption(\Redis::OPT_PREFIX, $this->config['prefix']);
            }
            return $r;
        }
    }

    protected function getTagKey($key, $tags = [])
    {
        if ($tags) {
            $prev = '';
            foreach ($tags as $tag) {
                $p = $this->get($tag);
                if (!$p) {
                    $p = $this->flush($tag);
                }
                $prev = md5($p . $prev);
            }
            return $key .  '#tag_' . $prev;
        } else {
            return $key;
        }
    }



    public function get($key, \Closure $closure = null, $ttl = null, $tags = [])
    {
        try {
            $rs  = $this->pop();
            $val = $rs->get($this->getTagKey($key, $tags));
            $this->push($rs);
            if ((!$val) && $closure) {
                $val = $closure();
                $this->set($key, $val, $ttl, $tags);
            } else if ($val) {
                $val = $val;
            }
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
                $key = $this->getTagKey($key);
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
        return $this->del(array_map(function($v){
            return ltrim($v,$this->config['prefix']);
        },$this->keys($key)));
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
            $ret = $rs->set($this->getTagKey($key, $tags), $val, $ttl);
            $this->push($rs);
            $this->setRetryCount();
            return $ret;
        } catch (\RedisException $e) {
            return $this->retry('set', func_get_args(), $e->getMessage(), $e->getCode());
        }

    }

}
