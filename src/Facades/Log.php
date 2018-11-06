<?php

namespace One\Facades;

/**
 * Class Log
 * @package One\Facades
 * @mixin \One\Log
 * @method  debug($data, $prefix = 'debug', $k = 0) static
 * @method  notice($data, $prefix = 'notice', $k = 0) static
 * @method  warn($data, $prefix = 'warn', $k = 0) static
 * @method  error($data, $prefix = 'error', $k = 0) static
 * @method  setTraceId($id) static
 * @method  bindTraceId($id) static
 * @method  flushTraceId() static
 * @method  getTraceId() static
 *
 */

class Log extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \One\Log::class;
    }
}