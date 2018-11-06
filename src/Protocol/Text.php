<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/12
 * Time: 16:32
 */

namespace One\Protocol;


class Text extends ProtocolAbstract
{

    const MAX_LEN = 8 * 1024 * 1024;

    public static function length($data)
    {
        if (strlen($data) >= self::MAX_LEN) {
            return -1;
        }
        $pos = strpos($data, "\n");
        if ($pos === false) {
            return 0;
        }
        return $pos + 1;
    }

    public static function encode($buf)
    {
        return $buf . "\n";
    }

    public static function decode($buf)
    {
        return trim($buf);
    }

}
