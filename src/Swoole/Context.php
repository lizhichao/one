<?php

namespace One\Swoole;

class Context
{
    private static $arr = [];

    public static function get($key, $default = null)
    {
        if (_CLI_) {
            return array_get(\Swoole\Coroutine::getContext(), $key, $default);
        } else {
            return array_get(self::$arr, $key, $default);
        }
    }

    public static function set($key, $val)
    {
        if (_CLI_) {
            \Swoole\Coroutine::getContext()[$key] = $val;
        } else {
            self::$arr[$key] = $val;
        }
    }
}