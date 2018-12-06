<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/4
 * Time: 17:52
 */

namespace One\Swoole;

use One\Facades\Cache;

class RpcServer
{
    const RPC_REMOTE_OBJ = '#RpcRemoteObj#';

    private static $class = [];

    private static $ids = [];

    private static $mids = [];

    public static function add($class_name, $method = '*')
    {
        if (!isset($class)) {
            self::$class[$class_name][$method] = self::$mids;
        }
    }


    private static function error($code, $msg)
    {
        return self::ret([
            'code' => $code,
            'msg'  => $msg
        ]);
    }

    /**
     * @param array $arr ['cache'=>1,'middle'=>[]]
     * @param \Closure $ce
     */
    public static function group($arr, $ce)
    {
        self::$mids = $arr;
        $ce();
        self::$mids = [];
    }

    /**
     * @param array $arr [i => call_id ,c => class ,f => func ,a => args,t => construct_args,s => 0 ]
     * @return mixed
     */
    public static function call($arr)
    {
        if (!isset($arr['c'], $arr['f'], $arr['i'])) {
            return self::error(400, '参数错误');
        }

        $c  = $arr['c'];
        $f  = $arr['f'];
        $id = $arr['i'];
        $a  = isset($arr['a']) ? $arr['a'] : [];
        $t  = isset($arr['t']) ? $arr['t'] : [];
        $s  = isset($arr['s']) ? $arr['s'] : 0;
        try {
            $info = self::isAllow($c, $f);
            $obj  = null;
            if (isset(self::$ids[$id])) {
                $obj = self::$ids[$id];
            }
            return self::ret(self::exec($c, $f, $a, $t, $info, $obj, $s), $id);
        } catch (\Exception $e) {
            return self::error($e->getCode(), $e->getMessage());
        }
    }

    private static function exec($c, $f, $a, $t, $info, $obj, $s)
    {
        if (isset($info['middle'])) {
            $mids = self::mids($info['middle']);
        } else {
            $mids = [];
        }

        $aciton = function () use ($c, $f, $a, $t, $info, $obj, $s) {
            if ($s === 1) {
                $res = $c::$f(...$a);
            } else {
                if (isset($info['cache'])) {
                    $k   = self::getCacheKey($c, $f, $a, $t);
                    $res = Cache::get($k);
                    if ($res !== false) {
                        return $res;
                    }
                }

                if ($obj === null) {
                    $obj = new $c(...$t);
                }
                if (method_exists($obj, $f) === false) {
                    throw new RpcException('method not exists', 404);
                }
                $res = $obj->$f(...$a);
            }
            if (isset($info['cache'])) {
                Cache::set($k, $res, $info['cache']);
            }
            return $res;
        };

        $df = self::getAction($mids, $aciton, $c, $f, $a, $t);
        return $df();
    }

    private static function getAction($mids, $action, ...$args)
    {
        foreach ($mids as $fn) {
            $action = $fn($action, ...$args);
        }
        return $action;
    }

    private static function mids($info)
    {
        $funcs = [];
        foreach ($info as $i => $v) {
            $funcs[] = function ($handler, ...$args) use ($v) {
                return function () use ($v, $handler, $args) {
                    array_unshift($args, $handler);
                    return call($v, $args);
                };
            };
        }
        return array_reverse($funcs);
    }

    private static function getCacheKey($c, $f, $a, $t)
    {
        return md5(json_encode([$c, $f, $a, $t]));
    }

    private static function isAllow($c, $f)
    {
        if (isset(self::$class[$c][$f]) || isset(self::$class[$c]['*'])) {
            return isset(self::$class[$c][$f]) ? self::$class[$c][$f] : self::$class[$c]['*'];
        } else {
            throw new RpcException('forbidden call', 403);
        }
    }

    private static function ret($r, $id = '')
    {
        if (is_object($r) || is_resource($r)) {
            self::$ids[$id] = $r;
            return msgpack_pack(self::RPC_REMOTE_OBJ);
        } else {
            return msgpack_pack($r);
        }
    }

    public static function close($id)
    {
        unset(self::$ids[$id]);
        return 1;
    }

    public static function ideHelper()
    {
        foreach (self::$class as $c => $fs) {
            if (isset($fs['*'])) {

            }
        }
    }

}
