<?php

namespace One\Database\Mysql;

class Build
{
    use WhereTrait;
    use StructTrait;

    protected $from = '';

    protected $pri_key = '';

    private $columns = [];

    private $model_name = '';

    protected $build = [];

    /**
     * @var Connect
     */
    private $connect;

    /**
     * @var EventBuild
     */
    public $model;

    public function __construct($connection, $model, $model_name, $table)
    {
        $this->from       = $table;
        $this->model      = $model;
        $this->model_name = $model_name;
        $this->connect    = new Connect($connection, $this->model_name);
    }

    private $withs = [];

    /**
     * @param $relation
     * @param null $closure
     * @return $this
     */
    public function with($relation, array $closure = null)
    {
        $this->withs[] = [$relation, $closure];
        return $this;
    }

    private function fillSelectWith($res, $call)
    {
        foreach ($this->withs as $v) {
            list($relation, $closure) = $v;
            list($drel, $nrel) = $this->getRel($relation);
            $q = $this->model->$drel();
            if ($closure !== null && $closure[0]) {
                $closure[0]->call($res,$q);
                unset($closure[0]);
            }
            if ($nrel) {
                if ($closure) {
                    $q->with($nrel, array_values($closure));
                } else {
                    $q->with($nrel);
                }
            }
            $q->$call($res)->merge($drel);
        }
        return $res;
    }

    private function getRel($rel)
    {
        $i = strpos($rel, '.');
        if ($i !== false) {
            $drel = substr($rel, 0, $i);
            $nrel = substr($rel, $i + 1);
            return [$drel, $nrel];
        } else {
            return [$rel, false];
        }
    }

    public function toSql()
    {
        return [
            $this->getSelectSql(), $this->build
        ];
    }

    protected function get($sql = '', $build = [], $all = false)
    {
        if ($sql === '') {
            $sql   = $this->getSelectSql();
            $build = $this->build;
        }

        if ($this->is_count === 1 && count($this->group_by) > 0) {
            $sql = str_replace($this->count_str, implode(',', $this->group_by), $sql);
            $i   = strripos($sql, ' limit ');
            $sql = substr($sql, 0, $i ? $i : null);
            $sql = "select {$this->count_str} from ({$sql}) as a";
        }

        if ($all) {
            return $this->connect->findAll($sql, $build);
        } else {
            return $this->connect->find($sql, $build);
        }
    }

    /**
     * @param string $sql
     * @param array $build
     * @return ListModel|static[]|Model[]
     */
    public function query($sql, array $build = [])
    {
        $info = $this->get($sql, $build, true);
        $ret  = new ListModel($info);
        if ($info) {
            $ret = $this->fillSelectWith($ret, 'setRelationList');
        }
        unset($this->model);
        return $ret;
    }


    protected function getData($all = false)
    {
        if ($all === false && $this->limit === 0) {
            $this->limit(1);
        }
        return $this->get('', [], $all);
    }

    private $set_top = [];

    public function setTop($key,$alias_name = null)
    {
        $ar = explode('.', $key);
        if (count($ar) < 2) {
            return $this;
        }
        $this->set_top[] = [
            $alias_name === null ? $ar[0] : $alias_name,
            $ar[0],
            $key
        ];
        return $this;
    }


    private function mvData($arr, $key)
    {
        $i  = strpos($key, '.');
        $k  = substr($key, 0, $i);
        $k1 = substr($key, $i + 1);
        $r  = [];
        if ($i && strlen($key) > $i) {
            if ($arr[$k] instanceof ListModel) {
                foreach ($arr[$k] as $val) {
                    $ret = $this->mvData($val, $k1);
                    if ($ret instanceof ListModel) {
                        $r = array_merge($r, $ret->jsonSerialize());
                    } else {
                        $r[] = $this->mvData($val, $k1);
                    }
                }
                return new ListModel($r);
            } else {
                return arrLine($arr[$k], $k1);
            }
        } else {
            return $arr[$key];
        }
    }


    /**
     * @param null $id
     * @return Model|null|static
     */
    public function find($id = null)
    {
        if ($id) {
            $this->where($this->getPriKey(), $id);
        }
        $info = $this->getData();
        if (empty($info)) {
            $info = null;
        } else {
            $info = $this->fillSelectWith($info, 'setRelationModel');
        }
        if(isset($this->set_top[0])){
            foreach ($this->set_top as $nr){
                $info->{$nr[0]} = $this->mvData($info, $nr[2]);
                unset($info->{$nr[1]});
            }
        }
        unset($this->model);
        return $info;
    }

    /**
     * @return ListModel|Model[]|static[]
     */
    public function findAll()
    {
        $info = $this->getData(true);
        $ret  = new ListModel($info);
        if ($info) {
            $ret = $this->fillSelectWith($ret, 'setRelationList');
        }
        if(isset($this->set_top[0])){
            foreach ($ret as $item){
                foreach ($this->set_top as $nr){
                    $item->{$nr[0]} = $this->mvData($item, $nr[2]);
                    unset($item->{$nr[1]});
                }
            }
        }
        unset($this->model);
        return $ret;
    }

    /**
     * @param null $id
     * @return array
     */
    public function findToArray($id = null)
    {
        $res = $this->find($id);
        if ($res === null) {
            return [];
        } else {
            return $res->toArray();
        }
    }

    /**
     * @param null $id
     * @return Model|static|Model
     */
    public function findOrErr($id = null, $msg = 'not find %s')
    {
        $res = $this->find($id);
        if ($res === null) {
            throw new \InvalidArgumentException(sprintf($msg, $id), 4004);
        } else {
            return $res;
        }
    }


    /**
     * 迭代所有数据
     * @param int $count 每次从数据库读取的数量
     * @return \Generator|static[]|Model[]
     */
    public function chunk($count = 100)
    {
        $val    = null;
        $arr    = isset($this->order_by[0]) ? explode(' ', $this->order_by[0]) : [$this->getPriKey()];
        $arr[1] = isset($arr[1]) ? strtolower(trim($arr[1])) : 'asc';
        $op     = $arr[1] === 'asc' ? '>' : '<';
        $this->limit($count)->orderBy($arr[0] . ' ' . $arr[1]);
        $where = $this->where;
        $model = $this->model;
        do {
            $this->where = $where;
            $this->model = $model;
            if ($val) {
                $this->where($arr[0], $op, $val);
            }
            $i   = 0;
            $ret = $this->findAll();
            foreach ($ret as $v) {
                $val = $v[$arr[0]];
                $i++;
                yield $v;
            }
        } while ($i === $count);
        unset($this->model);
    }

    /**
     * @return PageInfo
     */
    public function findAllPageInfo()
    {
        $page = new PageInfo();
        $info = $this->getData(true);
        $ret  = new ListModel($info);
        if ($info) {
            $ret = $this->fillSelectWith($ret, 'setRelationList');
        }
        $this->is_count = 1;
        $this->limit    = 0;
        $res            = $this->getData();
        $this->is_count = 0;
        $page->total    = $res->row_count;
        unset($this->model);
        $page->list = $ret;
        return $page;
    }

    protected $is_count = 0;

    /**
     * @return int
     */
    public function count()
    {
        $this->is_count = 1;
        $res            = $this->getData();
        $this->is_count = 0;
        unset($this->model);
        return $res->row_count;
    }

    protected $sum_column = '';

    /**
     * @param $column
     * @return int
     */
    public function sum($column)
    {
        $this->sum_column = $column;
        $res              = $this->getData();
        unset($this->model);
        return $res->sum_value;
    }

    /**
     * @param $sql
     * @param array $build
     * @param bool $is_insert
     * @return mixed
     */
    public function exec($sql, array $build = [], $is_insert = false)
    {
        if (count($build) === 0) {
            $p = $this->getConnect();
            $r = $p->exec($sql);
            $this->push($p);
        } else {
            $r = $this->connect->exec($sql, $build, $is_insert);
        }
        unset($this->model);
        return $r;
    }

    /**
     * @param $data
     * @return string
     */
    public function insert($data, $is_mulit = false)
    {
        $r = $this->connect->exec($this->getInsertSql($data, $is_mulit), $this->build, true);
        return $r;
    }

    /**
     * @param $data
     * @return int
     */
    public function update($data)
    {
        $r = $this->connect->exec($this->getUpdateSql($data), $this->build);
        return $r;

    }

    /**
     * @return int
     */
    public function delete()
    {
        $r = $this->connect->exec($this->getDeleteSql(), $this->build);
        return $r;

    }

    public function transactionId($id)
    {
        $this->connect->transactionId($id);
        return $this;
    }


    public function beginTransaction()
    {
        return $this->connect->beginTransaction();
    }

    public function rollBack()
    {
        return $this->connect->rollBack();
    }

    public function commit()
    {
        return $this->connect->commit();
    }

    /**
     * @param \Closure $call
     */
    public function transaction($call)
    {
        $this->connect->beginTransaction();
        try {
            $call();
        } catch (\Exception $e) {
            $this->connect->rollBack();
            throw $e;
        }
        $this->connect->commit();
        unset($this->model);
    }

    /**
     * @return \PDO
     */
    public function getConnect()
    {
        return $this->connect->pop();
    }

    /**
     * 返回pdo对象到列队
     */
    public function push($pdo)
    {
        $this->connect->push($pdo);
    }

    /**
     * @param $key
     * @return $this
     */
    public function setConnection($key)
    {
        $this->connect = new Connect($key, $this->model_name);
        return $this;
    }

    /**
     * @param string $from
     * @return $this
     */
    public function from($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function setPrikey($key)
    {
        $this->pri_key = $key;
        return $this;
    }

    /**
     * @param bool $p
     * @return $this
     */
    public function repeatStatement($p = true)
    {
        $this->connect->repeatStatement($p);
        return $this;
    }

    /**
     * @param array $column
     * @return $this
     */
    public function column(array $columns)
    {
        $this->columns = array_merge($this->columns, $columns);
        return $this;
    }

    private $distinct = '';

    /**
     * @return $this
     */
    public function distinct($column)
    {
        $this->distinct = $column;
        return $this;
    }

    /**
     * @param string $table
     * @param string $first 条件a
     * @param string $second 条件b
     * @return $this
     */
    public function leftJoin($table, $first, $second = null)
    {
        return $this->join($table, $first, $second, 'left');
    }

    /**
     * @param string $table
     * @param string $first
     * @param string $second
     * @return $this
     */
    public function rightJoin($table, $first, $second = null)
    {
        return $this->join($table, $first, $second, 'right');
    }

    private $joins = [];

    /**
     * @param string $table
     * @param string|\Closure $first
     * @param string $second
     * @param string $type
     * @return $this
     */
    public function join($table, $first, $second = null, $type = 'inner')
    {
        $join = new Join($table, $first, $second, $type);
        list($data, $str) = $join->get();
        $this->joins[] = $str;
        if ($data) {
            $this->whereRaw('', $data, '');
        }
        return $this;
    }

    private $group_by = [];

    /**
     * @param string $group_by
     * @return $this
     */
    public function groupBy($group_by)
    {
        $this->group_by[] = $group_by;
        return $this;
    }

    private $order_by = [];

    /**
     * @param $order_by
     * @return $this
     */
    public function orderBy($order_by)
    {
        $this->order_by[] = $order_by;
        return $this;
    }

    private $limit = 0;

    /**
     * @param int $limit
     * @param int $skip
     * @return $this
     */
    public function limit($limit, $skip = 0)
    {
        $this->limit = $skip . ',' . $limit;
        return $this;
    }

    private $having = [];

    /**
     * @param $key
     * @param null $operator
     * @param null $val
     * @param string $link
     * @return $this
     */
    public function having($key, $operator = null, $val = null, $link = ' and ')
    {
        if ($key instanceof \Closure) {
            $this->having[] = [null, '(', null, $link];
            $key($this);
            $this->having[] = [null, ')'];
        } else if ($val === null) {
            $val      = $operator;
            $operator = '=';
        }
        $this->having[] = [$key, $operator, $val, $link];
        return $this;
    }

    /**
     * @param $key
     * @param null $operator
     * @param null $val
     * @return $this
     */
    public function havingOr($key, $operator = null, $val = null)
    {
        return $this->having($key, $operator, $val, ' or ');
    }


    private function getHaving()
    {
        $prev = null;
        $data = [];
        $sql  = '';
        foreach ($this->having as $v) {
            if ($prev && isset($v[3])) {
                $sql .= $v[3];
            }
            if ($v[0] === null) {
                $sql .= $v[1];
            } else {
                $data[] = $v[2];
                $sql    .= $v[0] . $v[1] . '?';
            }
            if (isset($v[3])) {
                $prev = $v[0];
            }
        }
        if ($sql) {
            return [$data, ' having ' . $sql];
        } else {
            return [$data, ''];
        }
    }


    private function getWhere()
    {
        list($this->build, $where) = $this->toWhere();
        if ($where) {
            $where = ' where ' . $where;
        }
        return $where;
    }

    private function defaultColumn()
    {
        if (method_exists($this->model, __FUNCTION__)) {
            return $this->model->defaultColumn();
        } else {
            return ['*'];
        }
    }

    private $count_str = 'count(*) as row_count';

    protected function getSelectSql()
    {
        $sql = 'select';
        if ($this->is_count) {
            $column = ' ' . $this->count_str . ' ';
        } else if ($this->sum_column) {
            $column = ' sum(' . $this->sum_column . ') as sum_value ';
        } else if ($this->distinct) {
            $column = ' distinct ' . $this->distinct;
        } else if ($this->columns) {
            $column = implode(',', $this->columns);
        } else {
            $column = implode(',', $this->defaultColumn());
        }
        $sql .= ' ' . $column . ' from `' . $this->from . '`';
        foreach ($this->joins as $v) {
            $sql .= ' ' . $v;
        }
        $sql .= $this->getWhere();
        if ($this->group_by) {
            $sql .= ' group by ' . implode(',', $this->group_by);
        }
        if ($this->having) {
            list($d, $s) = $this->getHaving();
            $sql         .= $s;
            $this->build = array_merge($this->build, $d);
        }
        if ($this->order_by && $this->is_count == 0) {
            $sql .= ' order by ' . implode(',', $this->order_by);
        }
        if ($this->limit) {
            $sql .= ' limit ' . $this->limit;
        }
        return $sql;
    }


    private function getInsertSql($data, $is_mulit = false)
    {
        $sql = 'insert into `' . $this->from . '`';
        if ($is_mulit) {
            $build  = [];
            $keys   = array_keys($this->filter($data[0], true));
            $sql    .= ' (`' . implode('`,`', $keys) . '`)';
            $values = [];
            foreach ($data as $v) {
                $v        = $this->filter($v, true);
                $build    = array_merge($build, array_values($v));
                $values[] = '(' . substr(str_repeat(',?', count($keys)), 1) . ')';
            }
            $sql .= ' values ' . implode(',', $values);
        } else {
            $data  = $this->filter($data, true);
            $keys  = array_keys($data);
            $sql   .= ' (`' . implode('`,`', $keys) . '`)';
            $build = array_values($data);
            $sql   .= ' values (' . substr(str_repeat(',?', count($keys)), 1) . ')';
        }
        $this->build = $build;
        return $sql;
    }

    private function getUpdateSql($data)
    {
        $sql   = 'update `' . $this->from . '` set ';
        $build = [];
        $data  = $this->filter($data);
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $sql .= "`{$k}`={$v[0]},";
            } else {
                $sql     .= "`{$k}`=?,";
                $build[] = $v;
            }
        }
        $sql = substr($sql, 0, -1);
        $this->setPriWhere();
        $sql         .= $this->getWhere();
        $this->build = array_merge($build, $this->build);
        return $sql;
    }

    private function getDeleteSql()
    {
        $sql = 'delete from `' . $this->from . '`';
        $this->setPriWhere();
        $sql .= $this->getWhere();
        return $sql;
    }

    private function setPriWhere()
    {
        if (!$this->where) {
            $pri = $this->getPriKey();
            if (property_exists($this->model, $pri)) {
                $this->where($pri, $this->model->$pri);
            }
        }
    }


    public function __call($name, $arguments)
    {
        if (method_exists($this->model->relation(), $name)) {
            return $this->model->relation()->$name(...$arguments);
        } else if (method_exists($this->model, $name)) {
            return $this->model->$name(...$arguments);
        } else {
            throw new DbException('Undefined method ' . $name, 556);
        }
    }

}