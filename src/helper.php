<?php

if ( !function_exists('__') ) {
    function __(string $key, array $parameters=[])
    {
        return (new \One\I18n\Lang)->getTranslate(key: $key, parameters: $parameters);
    }
}

if (function_exists('config') === false) {
    /**
     * @param $path
     * @return mixed|null
     */
    function config($path, $flush = false)
    {
        static $config = null;
        $res = array_get($config, $path);
        if (!$res || $flush) {
            $p = strpos($path, '.');
            if ($p !== false) {
                $name          = substr($path, 0, $p);
                $config[$name] = require(_APP_PATH_ . '/Config/' . $name . '.php');
            } else {
                $config[$path] = require(_APP_PATH_ . '/Config/' . $path . '.php');
            }
            $res = array_get($config, $path);
        }
        return $res;
    }
}

if (function_exists('array_to_object') === false) {
    /**
     * @param array $arr
     * @param string $class_name
     * @return Object $class_name
     */
    function array_to_object($arr, $class_name)
    {
        $class = new $class_name;
        foreach ($arr as $key => $val) {
            $class->{$key} = $val;
        }
        return $class;
    }
}


/**
 * @param string $fn
 * @param array $args
 * @return mixed
 */
function call($fn, $args)
{
    if (strpos($fn, '@') !== false) {
        $fire = \One\Caller\Fire::callingFunctionsThatHaveAtsign(fn: $fn, args: $args);
    } else {
        $fire = \One\Caller\Fire::callingFunctionsThatHaveNotAtsign(fn: $fn, args: $args);
    }
    return $fire;
}


/**
 * @param array $arr
 * @param $key
 * @return mixed|null
 */
function array_get($arr, $key, $default = null)
{
    if (isset($arr[$key])) {
        return $arr[$key];
    } else if (strpos($key, '.') !== false) {
        $keys = explode('.', $key);
        foreach ($keys as $v) {
            if (isset($arr[$v])) {
                $arr = $arr[$v];
            } else {
                return $default;
            }
        }
        return $arr;
    } else {
        return $default;
    }
}


/**
 * @param array $arr
 * @param array $keys
 * @return mixed|null
 */
function array_get_not_null($arr, $keys)
{
    foreach ($keys as $v) {
        if (array_get($arr, $v) !== null) {
            return array_get($arr, $v);
        }
    }
    return null;
}

if (function_exists('uuid') === false) {
    /**
     * @return string
     */
    function uuid()
    {
        $str = uniqid('', true);
        $arr = explode('.', $str);
        $str = $arr[0] . base_convert($arr[1], 10, 16);
        $len = 32;
        while (strlen($str) <= $len) {
            $str .= bin2hex(random_bytes(4));
        }
        return substr($str, 0, $len);
    }
}

/**
 * 高精度任意转换 最多支持62进制
 * @param string $num
 * @param string $in
 * @param string $out
 * @return string
 */
function bc_base_convert($num, $in, $out)
{
    $str = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $num = "$num";
    $len = strlen($num);
    $n   = 0;
    for ($i = 0; $i < $len; $i++) {
        $sc = bcmul(strpos($str, $num[$i]), bcpow($in, $len - $i - 1));
        $n  = bcadd($n, $sc, 0);
    }
    $e = '';
    while ($n > 0) {
        $i = bcmod($n, $out);
        $e = $str[$i] . $e;
        $n = bcdiv($n, $out, 0);
    }
    return $e;
}


/**
 * @param $str
 * @param null $allow_tags
 * @return string
 */
function filter_xss($str, $allow_tags = null)
{
    $str = strip_tags($str, $allow_tags);
    if ($allow_tags !== null) {
        while (true) {
            $l   = strlen($str);
            $str = preg_replace('/(<[^>]+?)([\'\"\s]+on[a-z]+)([^<>]+>)/i', '$1$3', $str);
            $str = preg_replace('/(<[^>]+?)(javascript\:)([^<>]+>)/i', '$1$3', $str);
            if (strlen($str) == $l) {
                break;
            }
        }
    }
    return $str;
}


/**
 * @param $str
 * @param array $data
 * @return string
 */
function router($str, $data = [])
{
    $url = array_get(\One\Http\Router::$as_info, $str);
    if ($data) {
        $key  = array_map(function ($v) {
            return '{' . $v . '}';
        }, array_keys($data));
        $data = array_map(function ($v) {
            return urlencode($v);
        }, $data);
        $url  = str_replace($key, array_values($data), $url);
    }
    return $url;
}

if (function_exists('format_json') === false) {
    /**
     * 统一格式json输出
     */
    function format_json($data, $code, $id)
    {
        $arr = ['err' => $code, 'rid' => $id];
        if ($code) {
            $arr['msg'] = $data;
            $arr['res'] = '';
        } else {
            $arr['msg'] = '';
            $arr['res'] = $data;
        }
        return json_encode($arr);
    }
}

/**
 * 设置数组的key
 * @param $arr
 * @param $key
 * @param bool $unique
 * @return array
 */
function set_arr_key($arr, $key, $unique = true)
{
    $r = [];
    foreach ($arr as $v) {
        if ($unique) {
            $r[$v[$key]] = $v;
        } else {
            $r[$v[$key]][] = $v;
        }
    }
    return $r;
}

/**
 * 创建协程id
 * @param $call
 * @return string 返回协程id
 */
function one_go($call)
{
    if (_CLI_) {
        $log_id = \One\Facades\Log::getTraceId();
        return \Swoole\Coroutine::create(function () use ($call, $log_id) {
            $go_id = \One\Facades\Log::setTraceId($log_id);
            try {
                $call();
            } catch (\Throwable $e) {
                error_report($e);
            }
        });
    } else {
        return $call();
    }
}

/**
 * 获取协程id
 */
function get_co_id()
{
    if (_CLI_) {
        return \Swoole\Coroutine::getuid();
    } else {
        return -1;
    }
}

/**
 * 分布式redis加锁
 * @param $tag
 */
function redis_lock($tag)
{
    $time = time();
    $key  = 'linelock:' . $tag;
    while (!\One\Facades\Redis::setnx($key, $time + 3)) {
        $time = time();
        if ($time > \One\Facades\Redis::get($key) && $time > \One\Facades\Redis::getSet($key, $time + 3)) {
            break;
        } else {
            usleep(10);
        }
    }
}

/**
 * 分布式redis解锁
 * @param $tag
 */
function redis_unlock($tag)
{
    $key = 'linelock:' . $tag;
    \One\Facades\Redis::del($key);
}

/**
 * @param $key
 * @param null $default
 * @return mixed|null
 */
function env($key, $default = null)
{
    static $arr = [];
    if (empty($arr) && file_exists(_APP_PATH_ . '/app.ini')) {
        $arr = parse_ini_file(_APP_PATH_ . '/app.ini', true);
    }
    return array_get($arr, $key, $default);
}


function one_get_object_vars($obj)
{
    return get_object_vars($obj);
}


if (function_exists('error_report') === false) {
    function error_report(\Throwable $e)
    {
        \One\Facades\Log::error([
            'file'  => $e->getFile() . ':' . $e->getLine(),
            'msg'   => $e->getMessage(),
            'code'  => $e->getCode(),
            'trace' => $e->getTrace()
        ]);
    }
}


