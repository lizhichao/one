<?php


/**
 * @param $path
 * @return mixed|null
 */
function config($path)
{
    static $config = null;
    $res = array_get($config, $path);
    if (!$res) {
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

/**
 * @param string $fn
 * @param array $args
 * @return mixed
 */
function call($fn, $args)
{
    if (strpos($fn, '@') !== false) {
        $cl = explode('@', $fn);
        return call_user_func_array([new $cl[0], $cl[1]], $args);
    } else {
        return call_user_func_array($fn, $args);
    }
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

/**
 * @param bool $base62
 * @return string
 */
function uuid($base62 = true)
{
    $str = uniqid('', true);
    $arr = explode('.', $str);
    $str = $arr[0] . base_convert($arr[1], 10, 16);
    $len = 32;
    while (strlen($str) <= $len) {
        $str .= bin2hex(random_bytes(4));
    }
    $str = substr($str, 0, $len);
    if ($base62) {
        $str = str_replace(['+', '/', '='], '', base64_encode(hex2bin($str)));
    }
    return $str;
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

/**
 * 统一格式json输出
 */
function format_json($data, $code, $id)
{
    $arr = ['err' => $code, 'rid' => $id];
    if ($code) {
        $arr['msg'] = $data;
    } else {
        $arr['msg'] = '';
        $arr['res'] = $data;
    }
    return json_encode($arr);
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
 * 创建协成id
 * @param $call
 * @return string 返回协成id
 */
function one_go($call)
{
    if (_CLI_) {
        $co_id = get_co_id();
        return go(function () use ($call, $co_id) {
            $go_id = \One\Facades\Log::bindTraceId($co_id, true);
            try {
                $call();
            } catch (\Throwable $e) {
                \One\Facades\Log::flushTraceId($go_id);
                \One\Facades\Log::error([
                    'file'  => $e->getFile() . ':' . $e->getLine(),
                    'msg'   => $e->getMessage(),
                    'code'  => $e->getCode(),
                    'trace' => $e->getTrace()
                ]);
            }
            \One\Facades\Log::flushTraceId($go_id);
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


function error_report(\Throwable $e)
{
    \One\Facades\Log::error([
        'file' => $e->getFile().':'.$e->getLine(),
        'msg' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTrace()
    ]);
}
