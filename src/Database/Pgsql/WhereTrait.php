<?php

namespace One\Database\Mysql;

trait WhereTrait
{
    protected $where = [];

    /**
     * @param $key
     * @param null $operator
     * @param null $val
     * @param string $link
     * @return $this
     */
    public function where($key, $operator = null, $val = null, $link = ' and ')
    {
        if (is_array($key)) {
            $key = $this->filter($key);
            foreach ($key as $k => $v) {
                $this->where[] = [$k, '=', $v, $link];
            }
        } else if ($key instanceof \Closure) {
            $this->where[] = [null, '(', null, $link];
            $key($this);
            $this->where[] = [null, ')'];
        } else {
            if ($val === null) {
                $val = $operator;
                $operator = '=';
            }
            $this->where[] = [$key, $operator, $val, $link];
        }
        return $this;
    }

    /**
     * @param string|array|\Closure $key
     * @param null $operator
     * @param null $val
     * @return $this
     */
    public function whereOr($key, $operator = null, $val = null)
    {
        return $this->where($key, $operator, $val, ' or ');
    }

    /**
     * @param $key
     * @param array $val
     * @return $this
     */
    public function whereIn($key, array $val)
    {
        return $this->where($key, ' in ', $val);
    }

    /**
     * @param $key
     * @param array $val
     * @return $this
     */
    public function whereNotIn($key, array $val)
    {
        return $this->where($key, ' not in ', $val);
    }

    /**
     * @param $str
     * @param array|null $build_data
     * @param string $link
     * @return $this
     */
    public function whereRaw($str, array $build_data = null, $link = ' and ')
    {
        $this->where[] = [$str, null, $build_data, $link];
        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function whereNotNull($key)
    {
        return $this->where($key, ' is ', 'not null');
    }

    /**
     * @param $key
     * @return $this
     */
    public function whereNull($key)
    {
        return $this->where($key, ' is ', 'null');
    }

    /**
     * @return array
     */
    public function toWhere()
    {
        $where = '';
        $data = [];
        $prev = null;
        foreach ($this->where as $v) {
            if ($prev && isset($v[3])) {
                $where .= $v[3];
            }
            if ($v[0] === null) {
                $where .= $v[1];
            } else {
                if ($v[1] === null) {
                    $where .= $v[0];
                    if ($v[2]) {
                        $data = array_merge($data, $v[2]);
                    }
                } else if (is_array($v[2])) {
                    $data = array_merge($data, $v[2]);
                    $where .= $v[0] . $v[1] . '(' . substr(str_repeat(',?', count($v[2])), 1) . ')';
                } else if (trim($v[1]) == 'is') {
                    $where .= $v[0] . $v[1] . $v[2];
                } else {
                    $data[] = $v[2];
                    $where .= $v[0] . $v[1] . '?';
                }
            }
            if (isset($v[3])) {
                $prev = $v[0];
            }
        }
        return [$data, $where];
    }
}
