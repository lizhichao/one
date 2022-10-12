<?php

namespace One\Facades;


use One\Cache\File;
use One\Cache\Redis;

/**
 * Class Cache
 * @package Facades
 * @mixin \One\Cache\Redis
 * @mixin \Redis
 * @method string static get($key, \Closure $closure = null, $ttl = 0, $tags = []) static
 * @method bool static delRegex($key) static
 * @method bool static del($key) static
 * @method bool static flush($tag) static
 * @method bool static set($key, $val, $ttl = 0, $tags = []) static
 * @method Redis static setConnection($key)
 */
class Cache extends Facade
{
    protected static function getFacadeAccessor()
    {
        switch (config('cache.drive')) {
            case 'file':
                return File::class;
                break;
            case 'redis':
                return Redis::class;
                break;
            default:
                exit('no cache drive');
        }
    }
}
