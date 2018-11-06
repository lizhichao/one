<?php

namespace One\Facades;

abstract class Facade
{
    private static $accessor = [];

    abstract protected static function getFacadeAccessor();

    protected static function initArgs()
    {
        return [];
    }

    public static function __callStatic($method, $parameters)
    {
        $cl = static::getFacadeAccessor();
        if (!isset(self::$accessor[$cl])) {
            self::$accessor[$cl] = new $cl(...static::initArgs());
        }
        return self::$accessor[$cl]->$method(...$parameters);
    }

    public static function clear($class)
    {
        unset(self::$accessor[$class]);
    }

}
