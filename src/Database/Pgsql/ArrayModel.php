<?php

namespace One\Database\Pgsql;

class ArrayModel implements \ArrayAccess
{

    public function offsetExists($offset)
    {
        return (property_exists($this, $offset) || method_exists($this, $offset));
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

}