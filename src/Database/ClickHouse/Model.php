<?php

namespace One\Database\ClickHouse;

use One\Database\Mysql\ArrayModel;
use One\Database\Mysql\ListModel;
use One\Database\Mysql\PageInfo;
use One\Database\Mysql\Relation;
use One\Database\Mysql\RelationTrait;
use OneCk\Client;

/**
 * Class Model
 * @method static string insert($data)
 * @method static EventBuild cache($time)
 * @method static EventBuild with($relation, array $closure = null)
 * @method static ListModel|static[]|Model[] query($sql, array $build = [])
 * @method static Model|null|static find($id = null)
 * @method static ListModel|Model[]|static[] findAll()
 * @method static array findToArray($id = null)
 * @method static Model|static findOrErr($id = null, $msg = 'not find %s')
 * @method static \Generator|static[] chunk($count = 100)
 * @method static PageInfo findAllPageInfo()
 * @method static int count()
 * @method static int sum($column)
 * @method static mixed exec($sql, array $build = [], $is_insert = false)
 * @method static Client getConnect()
 * @method static EventBuild setConnection($key)
 * @method static EventBuild from($from)
 * @method static EventBuild column(array $columns)
 * @method static EventBuild distinct($column)
 * @method static EventBuild leftJoin($table, $first, $second = null)
 * @method static EventBuild limit($limit, $skip = 0)
 * @method static EventBuild where($key, $operator = null, $val = null, $link = ' and ')
 * @method static EventBuild whereOr($key, $operator = null, $val = null)
 * @method static EventBuild whereIn($key, array $val)
 * @method static EventBuild whereRaw($str, array $build_data = null, $link = ' and ')
 * @method static EventBuild whereNull($key)
 * @method static void flushTableInfo()
 * @mixin EventBuild
 * @mixin Relation
 */
class Model extends ArrayModel
{
    use RelationTrait;

    protected $_connection = 'default';

    protected $_cache_time = 600;

    private $_relation = null;

    CONST TABLE = '';

    private $_build = null;

    public function __construct($relation = null)
    {
        $this->_relation = $relation;
    }

    public function __relation()
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
        return $this->_build;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this, $name) === false) {
            return $this->build()->$name(...$arguments);
        } else {
            throw new ClickHouseException('call method ' . $name . ' fail , is not public method');
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
                $this->$name = $obj->model->__relation()->setRelation()->get();
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
