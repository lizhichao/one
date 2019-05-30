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
        return self::getObject()->$method(...$parameters);
    }

    public static function getObject()
    {
        $cl = static::getFacadeAccessor();
        if (!isset(self::$accessor[$cl])) {
            self::$accessor[$cl] = new $cl(...static::initArgs());
        }
        return self::$accessor[$cl];
    }

    public static function clear($class)
    {
        unset(self::$accessor[$class]);
    }

}
