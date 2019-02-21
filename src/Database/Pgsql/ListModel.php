<?php

namespace One\Database\Pgsql;

class ListModel implements \Iterator, \JsonSerializable
{
    private $index = 0;
    private $data = [];

    public function pluck($column, $is_key = false, $unique = false)
    {
        if ($is_key) {
            $r = [];
            if ($unique) {
                foreach ($this->data as $v) {
                    $r[$v[$column]][] = $v;
                }
            } else {
                foreach ($this->data as $v) {
                    $r[$v[$column]] = $v;
                }
            }
            return $r;
        } else {
            $r = [];
            foreach ($this->data as $v) {
                $r[] = $v[$column];
            }
            return $r;
        }
    }

    public function jsonSerialize()
    {
        return $this->data;
    }

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        return $this->index < count($this->data);
    }

    public function current()
    {
        return $this->data[$this->index];
    }

    public function next()
    {
        return $this->index++;
    }

    public function key()
    {
        return $this->index;
    }

    public function toArray()
    {
        $r = [];
        foreach ($this->data as $val) {
            $r[] = $val->toArray();
            unset($val);
        }
        $this->data = [];
        return $r;
    }

}