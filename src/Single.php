<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/7/13
 * Time: 下午4:35
 * 单列模式
 */

namespace One;


class Single
{

    private static $accessor = [];

    /**
     * @param $name
     * @param $arguments
     * @return static
     */
    public static function __callStatic($name, $arguments)
    {
        $key = get_called_class();
        if (!isset(self::$accessor[$key])) {
            self::$accessor[$key] = new static;
        }
        return self::$accessor[$key]->$name(...$arguments);
    }

    private function __construct()
    {

    }

    private function __clone()
    {

    }

}