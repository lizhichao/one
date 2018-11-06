<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/5/25
 * Time: 上午9:40
 */

namespace One;


trait ConfigTrait
{
    protected static $conf = [];

    public static function setConfig($config)
    {
        self::$conf = $config;
    }
}