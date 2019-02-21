<?php

namespace One\Database\Mysql;

class Join
{
    use WhereTrait;

    private $table = '';

    private $first = '';

    private $operator = '=';

    private $second = '';

    private $type = '';


    public function __construct($table, $first, $second, $type)
    {
        $this->table = $table;
        if ($first instanceof \Closure) {
            $first($this);
            $this->type = $type;
        } else {
            $this->first = $first;
            $this->second = $second;
            $this->type = $type;
        }
    }

    public function on($first, $second)
    {
        $this->first = $first;
        $this->second = $second;
        return $this;
    }

    public function get()
    {
        $s = $this->type . ' join ' . $this->table . ' on ' . $this->first . $this->operator . $this->second;
        list($data,$w) = $this->toWhere();
        if ($w) {
            $s .= ' and ' . $w;
        }
        return [$data,$s];
    }

}