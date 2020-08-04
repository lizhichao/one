<?php

namespace One\Database\ClickHouse;

use One\Facades\Cache;

class CacheBuild extends Build
{
    private $cache_time = 0;

    /**
     * 缓存时间(秒)
     * @param int $time
     */
    public function cache($time)
    {
        $this->cache_time = $time;
        return $this;
    }

    private $cache_tag = [];

    protected function get($sql = '', $build = [], $all = false)
    {
        if ($this->cache_time == 0) {
            return parent::get($sql, $build, $all);
        }
        return unserialize(Cache::get($this->getCacheKey($sql), function () use ($sql, $build, $all) {
            return serialize(parent::get($sql, $build, $all));
        }, $this->cache_time, $this->cache_tag));
    }

    public function join($table, $first, $second = null, $type = 'inner')
    {
        $this->cache_tag[] = 'join+' . $table;
        return parent::join($table, $first, $second, $type);
    }

    private function getCacheKey($str = '')
    {
        $table = $this->from;
        $hash  = sha1($str . $this->getSelectSql() . json_encode($this->build));
        return "DB#{$table}#{$hash}";
    }
}