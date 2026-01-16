<?php

namespace One\Database\Mysql;

class ArrayModel extends \stdClass implements \ArrayAccess
{

    public function offsetExists($offset): bool
    {
        return (property_exists($this, $offset) || method_exists($this, $offset));
    }

    public function offsetSet($offset, $value): void
    {
        $this->$offset = $value;
    }

    public function offsetGet($offset): mixed
    {
        return $this->$offset;
    }

    public function offsetUnset($offset): void
    {
        unset($this->$offset);
    }

}