<?php

namespace One\Database\Mysql;

class EventBuild extends CacheBuild
{
    private $events = [];

    public function __construct($connection, $model, $model_name, $table)
    {
        parent::__construct($connection, $model, $model_name, $table);
        $this->events = $this->model->events();
    }

    protected function get($sql, array $build = [], $all = false)
    {
        if ($this->callBefre(__FUNCTION__, $sql, $build) !== false) {
            $ret = parent::get($sql, $build, $all);
            $this->callAfter(__FUNCTION__, $ret, $sql, $build);
            return $ret;
        }
    }
    
    /**
     * @param $data
     * @return int
     */
    public function update($data)
    {
        if ($this->callBefre(__FUNCTION__, $data) !== false) {
            $ret = parent::update($data);
            $this->callAfter(__FUNCTION__, $ret, $data);
            return $ret;
        }
    }

    /**
     * @return int
     */
    public function delete()
    {
        if ($this->callBefre(__FUNCTION__) !== false) {
            $ret = parent::delete();
            $this->callAfter(__FUNCTION__, $ret);
            return $ret;
        }
    }

    /**
     * @param $data
     * @param bool $is_mulit
     * @return string
     */
    public function insert($data, $is_mulit = false)
    {
        if ($this->callBefre(__FUNCTION__, $data) !== false) {
            $ret = parent::insert($data, $is_mulit);
            $this->callAfter(__FUNCTION__, $ret, $data);
            return $ret;
        }
    }

    private function callBefre($name, & $arg = null)
    {
        $key = 'before' . ucfirst($name);
        if (isset($this->events[$key])) {
            return $this->events[$key]($this, $arg);
        } else {
            return true;
        }
    }

    private function callAfter($name, & $result, & $arg = null)
    {
        $key = 'after' . ucfirst($name);
        if (isset($this->events[$key])) {
            $this->events[$key]($result, $arg);
        }
    }


}