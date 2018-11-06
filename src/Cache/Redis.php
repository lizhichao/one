<?php

namespace One\Cache;

use One\ConfigTrait;
use One\Swoole\Pools;

/**
 * Class Redis
 * @package One\Cache
 * @mixin \Redis
 */
class Redis extends Cache
{
    use ConfigTrait, Pools;

    /**
     * @var \Redis
     */
    private $driver;

    private $key = '';

    private $config = [];

    public function __construct($key = 'default')
    {
        $this->setConnection($key);
    }

    public function __call($name, $arguments)
    {
        $rs = $this->pop();
        $ret = $rs->$name(...$arguments);
        $this->push($rs);
        return $ret;
    }


    public function setConnection($key)
    {
        $this->key = $key;
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
        $rs = $this->pop();
        $val = $rs->get($this->getTagKey($key, $tags));
        if ((!$val) && $closure) {
            $val = $closure();
            $this->set($key, $val, $ttl, $tags);
        } else if ($val) {
            $val = unserialize($val);
        }
        $this->push($rs);
        return $val;
    }

    public function del($key)
    {
        if (is_string($key)) {
            $key = self::$conf['prefix'] . $key;
        }
        $rs = $this->pop();
        $ret = $rs->del($key);
        $this->push($rs);
        return $ret;
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
        $rs = $this->pop();
        $ret = $rs->set($this->getTagKey($key, $tags), serialize($val), $ttl);
        $this->push($rs);
        return $ret;
    }

}
