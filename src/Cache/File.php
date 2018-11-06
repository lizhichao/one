<?php

namespace One\Cache;

use One\ConfigTrait;

class File extends Cache
{
    use ConfigTrait;

    public function __construct()
    {
        $this->mkdir();
    }

    private function mkdir()
    {
        if (!is_dir(self::$conf['path'])) {
            mkdir(self::$conf['path'], 0755, true);
        }
    }

    public function get($key, \Closure $closure = null, $ttl = 0, $tags = [])
    {
        $k = $this->getTagKey($key, $tags);
        $f = $this->getFileName($k);
        if (file_exists($f)) {
            $str = file_get_contents($f);
            if ($str) {
                $time = substr($str, 0, 10);
                $str = substr($str, 10);
                if ($time > time()) {
                    return unserialize($str);
                }
            }
        }
        if ($closure) {
            $val = $closure();
            $this->set($key, $val, $ttl, $tags);
            return $val;
        } else {
            $this->del($key);
            return false;
        }
    }

    public function delRegex($key)
    {
        $this->del(glob($key));
    }

    public function flush($tag)
    {
        $id = md5(uuid());
        $this->set($tag, $id);
        return $id;
    }

    public function set($key, $val, $ttl = 0, $tags = [])
    {
        $key = $this->getTagKey($key, $tags);
        $file = $this->getFileName($key);
        file_put_contents($file, (time() + $ttl) . serialize($val));
    }

    public function del($key)
    {
        if (is_array($key)) {
            foreach ($key as $k) {
                @unlink($this->getFileName($k));
            }
        } else {
            return @unlink($this->getFileName(self::$conf['prefix'] . $key));
        }
    }

    private function getFileName($key)
    {
        return self::$conf['path'] . '/' . $key;
    }

}