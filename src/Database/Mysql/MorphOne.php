<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/3/20
 * Time: 17:37
 */

namespace One\Database\Mysql;

class MorphOne
{
    protected $remote_type = [];

    protected $remote_type_id = [];

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var ListModel
     */
    protected $list_model;

    protected $self_id = '';

    protected $self_type = '';


    public function __construct($remote_type, $remote_type_id, $self_type, $self_id, $model)
    {
        $this->self_id = $self_id;
        foreach ($remote_type as $type => $remote_model) {
            $this->remote_type[$type] = new $remote_model($this);
        }
        $this->remote_type_id = $remote_type_id;
        $this->model          = $model;
        $this->self_type      = $self_type;
    }

    /**
     * @param array $calls [\Closure(Model)]
     * @return $this
     */
    public function each(array $calls)
    {
        foreach ($calls as $type => $call) {
            if (isset($this->remote_type[$type])) {
                $call->call($this->remote_type[$type]);
            }
        }
        return $this;
    }

    public function setRelation()
    {
        $type = $this->model[$this->self_type];
        foreach ($this->remote_type as $type1 => $remote_model) {
            if ($type1 == $type) {
                $remote_model->where($this->remote_type_id[$type], $this->model[$this->self_id]);
            } else {
                unset($this->remote_type[$type1]);
            }
        }
        return $this;
    }

    public function setRelationList(ListModel $list_model)
    {
        $this->list_model = $list_model;
        $types            = $this->list_model->pluck($this->self_type, true, true);
        foreach ($this->remote_type as $type => $remote_model) {
            if (isset($types[$type])) {
                $ids = array_column($types[$type], $this->self_id);
                $remote_model->whereIn($this->remote_type_id[$type], $ids);
            } else {
                unset($this->remote_type[$type]);
            }
        }
        return $this;
    }


    public function setRelationModel(Model $model)
    {
        $this->model = $model;
        return $this->setRelation();
    }

    public function get()
    {
        return end($this->remote_type)->find();
    }

    public function merge($key)
    {
        if ($this->list_model === null) {
            $this->model->$key = $this->get();
        } else {
            $list_data = [];
            foreach ($this->remote_type as $type => $remote_model) {
                $list_data[$type] = $remote_model->findAll()->pluck($this->remote_type_id[$type], true);
            }
            foreach ($this->list_model as $val) {
                $type      = $val[$this->self_type];
                $id        = $val[$this->self_id];
                $val->$key = isset($list_data[$type][$id]) ? $list_data[$type][$id] : null;
            }
        }
        unset($this->model, $this->remote_type);
    }


}