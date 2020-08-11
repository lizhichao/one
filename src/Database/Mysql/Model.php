<?php

namespace One\Database\Mysql;


/**
 * Class Model
 * @method static EventBuild insert()
 * @method static EventBuild cache()
 * @method static EventBuild with()
 * @method static EventBuild query()
 * @method static EventBuild find()
 * @method static EventBuild findAll()
 * @method static EventBuild findToArray()
 * @method static EventBuild findOrErr()
 * @method static EventBuild chunk()
 * @method static EventBuild findAllPageInfo()
 * @method static EventBuild count()
 * @method static EventBuild sum()
 * @method static EventBuild exec()
 * @method static EventBuild getConnect()
 * @method static EventBuild setConnection()
 * @method static EventBuild from()
 * @method static EventBuild column()
 * @method static EventBuild distinct()
 * @method static EventBuild leftJoin()
 * @method static EventBuild rightJoin()
 * @method static EventBuild groupBy()
 * @method static EventBuild orderBy()
 * @method static EventBuild limit()
 * @method static EventBuild where()
 * @method static EventBuild whereOr()
 * @method static EventBuild whereIn()
 * @method static EventBuild whereNotIn()
 * @method static EventBuild repeatStatement()
 * @method static EventBuild whereRaw()
 * @method static EventBuild whereNotNull()
 * @method static EventBuild whereNull()
 * @method static EventBuild flushTableInfo()
 * @mixin CacheBuild
 * @mixin Relation
 */
class Model extends ArrayModel
{
    use RelationTrait;

    protected $_connection = 'default';

    protected $_cache_time = 600;

    protected $_cache_key_column = [];

    protected $_ignore_flush_cache_column = [];

    private $_relation = null;

    CONST TABLE = '';

    protected $_pri_key = '';

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
        if ($this->_cache_key_column) {
            $this->_build->cacheColumn($this->_cache_key_column);
        }
        if ($this->_ignore_flush_cache_column) {
            $this->_build->ignoreColumn($this->_ignore_flush_cache_column);
        }
        if ($this->_pri_key) {
            $this->_build->setPrikey($this->_pri_key);
        }
        return $this->_build;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name) === false) {
            return $this->build()->$name(...$arguments);
        } else {
            throw new DbException('call method ' . $name . ' fail , is not public method');
        }
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

    public function toArray()
    {
        $obj = one_get_object_vars($this);
        foreach ($obj as &$v) {
            if (is_object($v)) {
                $v = $v->toArray();
            }
        }
        return $obj;
    }


    public function events()
    {
        return [];
    }
}
