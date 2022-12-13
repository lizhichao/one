<?php

namespace One\Database\Mysql;

class ListModel implements \Iterator, \JsonSerializable, \ArrayAccess
{
    private $index = 0;
    private $data  = [];
    private $len   = 0;


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

    public function offsetExists($offset): bool
    {
        return property_exists($this->data, $offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->data[$offset] = $value;
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset];
    }

    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
        $this->len--;
    }

    public function jsonSerialize(): mixed
    {
        return $this->data;
    }

    public function __construct($data)
    {
        $this->data = $data ? $data : [];
        $this->len  = count($this->data);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }

    public function valid(): bool
    {
        return $this->index < $this->len;
    }

    public function current(): mixed
    {
        return $this->data[$this->index];
    }

    public function next(): void
    {
        $this->index++;
    }

    public function key(): mixed
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