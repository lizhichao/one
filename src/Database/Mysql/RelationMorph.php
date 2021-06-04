<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/3/22
 * Time: 14:40
 */

namespace One\Database\Mysql;


class RelationMorph
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

    public function __call($name, $arguments)
    {
        foreach ($this->remote_type as $remote_model) {
            $remote_model->$name(...$arguments);
        }
        return $this;
    }

}