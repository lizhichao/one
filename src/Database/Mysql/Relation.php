<?php

namespace One\Database\Mysql;


class Relation
{
    protected $self_column;
    protected $third_model;
    protected $third_column;
    protected $model;
    protected $list_model = null;

    private $is_set = false;

    public function __construct($self_column, $third_model, $third_column, $model)
    {
        $this->self_column  = $self_column;
        $this->third_model  = new $third_model($this);
        $this->third_column = $third_column;
        $this->model        = $model;
    }

    public function setRelation()
    {
        $this->third_model->where($this->third_column, $this->model[$this->self_column]);
        return $this;
    }

    public function setRelationModel($model)
    {
        $this->model = $model;
        return $this->setRelation();
    }

    public function setRelationList($list_model)
    {
        $this->list_model = $list_model;
        $this->third_model->whereIn($this->third_column, $this->list_model->pluck($this->self_column));
        return $this;
    }

    public function __call($name, $arguments)
    {
        if ($this->is_set === false) {
            $this->setRelation();
            $this->is_set = true;
        }
        return $this->third_model->$name(...$arguments);
    }

}