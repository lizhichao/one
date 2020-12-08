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
            return $this->retry($name, $arguments, $e->getMessage(), $e->getCode(), $rs->mykey);
        }
    }

    private function setRetryCount()
    {
        $this->retry_count = $this->config['max_connect_count'] + 1;
    }

    private function retry($name, $arguments, $msg, $code, $mykey)
    {
        Log::warn('retry ' . $name);
        $this->setConnCount($mykey, -1);
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
        $config = $this->config;
        $mykey  = $this->key;
        if (isset($config['is_cluster']) && $config['is_cluster'] === true) {
            return new \RedisCluster(...$config['args']);
        } else {
            $r        = new \Redis();
            $r->mykey = $mykey;
            $r->connect($config['host'], $config['port'], 0);
            if (empty($config['auth']) === false) {
                $r->auth($config['auth']);
            }

            if (empty($config['db']) === false) {
                $r->select($config['db']);
            }

            if (empty($config['prefix']) === false) {
                $r->setOption(\Redis::OPT_PREFIX, $config['prefix']);
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
            return $key . '#tag_' . $prev;
        } else {
            return $key;
        }
    }


    public function get($key, \Closure $closure = null, $ttl = null, $tags = [])
    {
        $mykey = $this->key;
        try {
            $tk  = $this->getTagKey($key, $tags);
            $rs  = $this->pop();
            $val = $rs->get($tk);
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
            return $this->retry('get', func_get_args(), $e->getMessage(), $e->getCode(), $mykey);
        }
    }

    public function del($key)
    {
        $mykey = $this->key;
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
            return $this->retry('del', func_get_args(), $e->getMessage(), $e->getCode(), $mykey);
        }

    }

    public function delRegex($key)
    {
        return $this->del(array_map(function ($v) {
            return ltrim($v, $this->config['prefix']);
        }, $this->keys($key)));
    }

    public function flush($tag)
    {
        $id = md5(uuid());
        $this->set($tag, $id);
        return $id;
    }

    public function set($key, $val, $ttl = null, $tags = [])
    {
        $mykey = $this->key;
        try {
            $tk  = $this->getTagKey($key, $tags);
            $rs  = $this->pop();
            $ret = $rs->set($tk, $val, $ttl);
            $this->push($rs);
            $this->setRetryCount();
            return $ret;
        } catch (\RedisException $e) {
            return $this->retry('set', func_get_args(), $e->getMessage(), $e->getCode(), $mykey);
        }

    }

}
