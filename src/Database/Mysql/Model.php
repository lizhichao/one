<?php

namespace One\Database\Mysql;


/**
 * Class Model
 * @method static string insert($data, $is_mulit = false)
 * @method static EventBuild cache($time)
 * @method static EventBuild with($relation, array $closure = null)
 * @method static ListModel|static[]|Model[] query($sql, array $build = [])
 * @method static Model|null|static find($id = null)
 * @method static ListModel|Model[]|static[] findAll()
 * @method static array findToArray($id = null)
 * @method static Model findOrErr($id = null, $msg = 'not find %s')
 * @method static \Generator|static[] chunk($count = 100)
 * @method static PageInfo findAllPageInfo()
 * @method static int count()
 * @method static int sum($column)
 * @method static mixed exec($sql, array $build = [], $is_insert = false)
 * @method static \PDO getConnect()
 * @method static EventBuild setConnection($key)
 * @method static EventBuild from($from)
 * @method static EventBuild column(array $columns)
 * @method static EventBuild distinct($column)
 * @method static EventBuild leftJoin($table, $first, $second = null)
 * @method static EventBuild limit($limit, $skip = 0)
 * @method static EventBuild where($key, $operator = null, $val = null, $link = ' and ')
 * @method static EventBuild whereOr($key, $operator = null, $val = null)
 * @method static EventBuild whereIn($key, array $val)
 * @method static EventBuild repeatStatement($p = true)
 * @method static EventBuild whereRaw($str, array $build_data = null, $link = ' and ')
 * @method static EventBuild whereNull($key)
 * @method static void flushTableInfo()
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
