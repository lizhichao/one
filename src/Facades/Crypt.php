<?php


namespace One\Facades;

use One\Crypt\Openssl;

/**
 * Class Crypt
 * @package One\Facades
 * @mixin Openssl
 * @method string hash($str) static
 * @method bool verifyHash($str, $hash) static
 * @method string decode($secret) static
 * @method string encode($secret) static
 * @method string sign($data) static
 * @method string checkSign($data, $sign) static
 */
class Crypt extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Openssl::class;
    }
}
