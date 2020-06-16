<?php

namespace One\Swoole;

class Context
{
    public static function get($key, $default = null)
    {
        return array_get(\Swoole\Coroutine::getContext(), $key, $default);
    }

    public static function set($key, $val)
    {
        \Swoole\Coroutine::getContext()[$key] = $val;
    }
}