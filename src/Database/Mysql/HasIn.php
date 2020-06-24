<?php

namespace One\Database\Mysql;

class HasIn extends Relation
{
    public function get()
    {
        return $this->third_model->findAll();
    }

    public function setRelationList($list_model)
    {
        $this->list_model = $list_model;
        $this->third_model->whereIn($this->third_column,
            $this->getIds(implode(',', $this->list_model->pluck($this->self_column)))
        );
        return $this;
    }

    private function getIds($str)
    {
        $arr = explode(',', $str);
        if (!$arr) {
            return [];
        }
        $arr = array_filter($arr, 'trim');
        $arr = array_unique($arr);
        return $arr;
    }

    public function setRelation()
    {
        $this->third_model->whereIn($this->third_column, $this->getIds($this->model[$this->self_column]));
        return $this;
    }


    public function merge($key)
    {
        if ($this->list_model === null) {
            $this->model->$key = $this->get();
        } else {
            $third_arr = $this->get()->pluck($this->third_column, true);
            foreach ($this->list_model as $val) {
                $ids = $this->getIds($val[$this->self_column]);
                if ($ids) {
                    $r = [];
                    foreach ($ids as $id) {
                        if (isset($third_arr[$id])) {
                            $r[] = $third_arr[$id];
                        }
                    }
                    $val->$key = new ListModel($r);
                } else {
                    $val->$key = new ListModel([]);
                }
            }
        }
        unset($this->model, $this->third_model);
    }


}