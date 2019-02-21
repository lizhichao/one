<?php

namespace One\Database\Pgsql;

class HasOne extends Relation
{
    public function get()
    {
        return $this->third_model->find();
    }

    public function merge($key)
    {
        if ($this->list_model === null) {
            $this->model->$key = $this->get();
        } else {
            $third_arr = $this->third_model->findAll()->pluck($this->third_column, true);
            foreach ($this->list_model as $val) {
                $k         = $val[$this->self_column];
                $val->$key = isset($third_arr[$k]) ? $third_arr[$k] : null;
            }
        }
        unset($this->model, $this->third_model);
    }
}