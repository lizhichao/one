<?php

namespace One\Database\Mysql;


/**
 * Class Model
 * @mixin CacheBuild
 * @method static transaction(\Closure $call)
 * @method int insert(array $data) static
 * @method Model find($val) static
 * @method Build where($key, $operator = null, $val = null, $link = ' and ') static
 * @method Build whereIn($key, array $val) static
 */
class Model extends ArrayModel
{
    use RelationTrait;

    protected $_connection = 'default';

    protected $_cache_time = 600;

    protected $_cache_column = [];

    protected $_ignore_flush_cache_column = [];

    protected $_relation = null;

    CONST TABLE = '';

    private $_build = null;

    public function __construct($relation = null)
    {
        $this->_relation = $relation;
    }

    public function relation()
    {
        return $this->_relation;
    }

    private function build()
    {
        if (!$this->_build) {
            $this->_build = new EventBuild($this->_connection, $this, get_called_class(), static::TABLE);
        }
        if ($this->_cache_time > 0) {
            $this->_build->cache($this->_cache_time);
        }
        if ($this->_cache_column) {
            $this->_build->cacheColumn($this->_cache_column);
        }
        if ($this->_ignore_flush_cache_column) {
            $this->_build->ignoreColumn($this->_ignore_flush_cache_column);
        }
        return $this->_build;
    }

    public function __call($name, $arguments)
    {
        return $this->build()->$name(...$arguments);
    }

    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }

    public function __get($name)
    {
        if (method_exists($this, $name)) {
            $obj = $this->$name();
            if ($obj instanceof Build) {
                $this->$name = $obj->model->relation()->setRelation()->get();
            } else {
                $this->$name = $obj->setRelation()->get();
            }
            return $this->$name;
        }
    }

    public function events()
    {
        return [];
    }
}
