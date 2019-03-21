<?php

namespace One\Database\Mysql;

class ListModel implements \Iterator, \JsonSerializable, \ArrayAccess
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

    public function offsetExists($offset)
    {
        return property_exists($this->data, $offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
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
        }
        $this->data = [];
        return $r;
    }

    public function __get($name)
    {
        if (method_exists($this->data[0], $name)) {
            $this->data[0]->$name()->setRelationList($this)->merge($name);
        } else {
            throw new DbException('not find property ' . $name);
        }
    }


}