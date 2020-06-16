<?php


namespace One;


class Context
{
    private static $arr = [];

    public static function get($key, $default = null)
    {
        return array_get(self::$arr, $key, $default);
    }

    public static function set($key, $val)
    {
        self::$arr[$key] = $val;
    }
}