<?php

namespace One\Database\Mysql;

class EventBuild extends CacheBuild
{

    protected function get($sql = '', $build = [], $all = false)
    {
        if ($this->callBefre(__FUNCTION__, $sql) !== false) {
            $ret = parent::get($sql, $build, $all);
            $this->callAfter(__FUNCTION__, $ret);
            return $ret;
        }
    }

    /**
     * @param $data
     * @return int
     */
    public function update($data)
    {
        $ret = null;
        if ($this->callBefre(__FUNCTION__, $data) !== false) {
            $ret = parent::update($data);
            $this->callAfter(__FUNCTION__, $ret, $data);
        }
        unsert($this->model);
        return $ret;
    }

    /**
     * @return int
     */
    public function delete()
    {
        $ret = null;
        if ($this->callBefre(__FUNCTION__) !== false) {
            $ret = parent::delete();
            $this->callAfter(__FUNCTION__, $ret);
        }
        unsert($this->model);
        return $ret;
    }

    /**
     * @param $data
     * @param bool $is_mulit
     * @return string
     */
    public function insert($data, $is_mulit = false)
    {
        $ret = null;
        if ($this->callBefre(__FUNCTION__, $data) !== false) {
            $ret = parent::insert($data, $is_mulit);
            $this->callAfter(__FUNCTION__, $ret, $data);
        }
        unset($this->model);
        return $ret;
    }

    private function callBefre($name, & $arg = null)
    {
        $m = 'onBefore' . ucfirst($name);
        if(method_exists($this->model,$m)){
            return $this->model->$m($this, $arg);
        } else {
            return true;
        }
    }

    private function callAfter($name, & $result, & $arg = null)
    {
        $m = 'onAfter' . ucfirst($name);
        if(method_exists($this->model,$m)){
            $this->model->$m($result, $arg);
        }
    }


}