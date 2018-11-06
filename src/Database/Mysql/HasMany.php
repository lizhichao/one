<?php

namespace One\Database\Mysql;

class HasMany extends Relation
{
    public function get()
    {
        return $this->third_model->findAll();
    }

    public function merge($key)
    {
        $third_arr = $this->get()->pluck($this->third_column, true, true);
        foreach ($this->list_model as $val) {
            $k = $val[$this->self_column];
            $val->$key = isset($third_arr[$k]) ? new ListModel($third_arr[$k]) : null;
        }
        unset($this->model,$this->third_model);
    }

}