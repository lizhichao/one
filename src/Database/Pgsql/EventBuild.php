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

    protected function getData($all = false)
    {
        if ($this->callBefre(__FUNCTION__, $id) !== false) {
            $ret = parent::getData($all);
            $this->callAfter(__FUNCTION__, $ret, $id);
            return $ret;
        }
    }

    public function find($id = null)
    {
        if ($this->callBefre(__FUNCTION__, $id) !== false) {
            $ret = parent::find($id);
            $this->callAfter(__FUNCTION__, $ret, $id);
            return $ret;
        }
    }

    public function findAll()
    {
        if ($this->callBefre(__FUNCTION__) !== false) {
            $ret = parent::findAll();
            $this->callAfter(__FUNCTION__, $ret);
            return $ret;
        }
    }

    public function update($data)
    {
        if ($this->callBefre(__FUNCTION__, $data) !== false) {
            $ret = parent::update($data);
            $this->callAfter(__FUNCTION__, $ret, $data);
            return $ret;
        }
    }

    public function delete()
    {
        if ($this->callBefre(__FUNCTION__) !== false) {
            $ret = parent::delete();
            $this->callAfter(__FUNCTION__, $ret);
            return $ret;
        }
    }

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