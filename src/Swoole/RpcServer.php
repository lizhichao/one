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
        if (is_array($method)) {
            foreach ($method as $m) {
                self::$class[$class_name][$m] = self::$mids;
            }
        } else {
            self::$class[$class_name][$method] = self::$mids;
        }
    }


    private static function error($code, $msg)
    {
        return self::ret([
            'err' => $code,
            'msg' => $msg
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
     * @param array $arr [i => call_id ,c => class ,f => func ,a => args,t => construct_args,s => 0,o=>token ]
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
        $o  = isset($arr['o']) ? $arr['o'] : null;
        try {
            $info = self::isAllow($c, $f);
            $obj  = null;
            if (isset(self::$ids[$id])) {
                $obj = self::$ids[$id];
            }
            return self::ret(self::exec($c, $f, $a, $t, $info, $obj, $s, $o), $id);
        } catch (\Exception $e) {
            return self::error($e->getCode(), $e->getMessage());
        }
    }

    private static function exec($c, $f, $a, $t, $info, $obj, $s, $o)
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

        $df = self::getAction($mids, $aciton, $o, $c, $f, $a, $t);
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


    public static function ideHelper($host, $px)
    {
        $host    = trim($host);
        $is_http = strpos($host, 'tcp') === 0 ? 0 : 1;
        if ($is_http) {
            $str = file_get_contents(__DIR__ . '/RpcClientHttp.php');
        } else {
            $str = file_get_contents(__DIR__ . '/RpcClientTcp.php');
        }
        $i = strpos($str, 'class');
        $r = "<?php\nnamespace {$px}{\n";
        $r .= self::tab(4) . substr($str, $i);
        $r .= "\n";
        foreach (self::$class as $c => $fs) {
            if (isset($fs['*'])) {
                $r .= self::getClassInfo($c, $px, $host, $is_http);
            }
        }
        return $r;
    }

    private static function getClassInfo($class, $px, $host, $is_http)
    {
        $px    = $px ? $px . '\\' : '';
        $class = new \ReflectionClass($class);
        $funcs = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        $r = 'namespace ' . $px . $class->getNamespaceName() . "{\n";
        $r .= self::tab(3) . "/**\n";
        foreach ($funcs as $func) {

            if (strpos($func->name, '__') === 0 && $func->name !== '__construct') {
                continue;
            }

            $return = $func->getReturnType() ? $func->getReturnType() : 'mixed';
            $r      .= self::tab(4) . "* @method {$return} {$func->name}(";
            $params = [];
            foreach ($func->getParameters() as $param) {
                if ($param->getType()) {
                    $params[] = $param->getType() . ' $' . $param->getName();
                } else {
                    $params[] = '$' . $param->getName();
                }
            }
            $r .= implode(',', $params) . ")";
            if ($func->isStatic()) {
                $r .= ' static';
            }
            $r .= "\n";
        }
        $name = str_replace($class->getNamespaceName() . '\\', '', $class->getName());
        $r    .= self::tab(4) . "*/\n";
        if ($is_http) {
            $r .= self::tab(4) . "class {$name} extends \\{$px}RpcClientHttp { \n";
        } else {
            $r .= self::tab(4) . "class {$name} extends \\{$px}RpcClientTcp { \n";
        }
        $r .= self::tab(8) . "protected \$_rpc_server = '{$host}';\n";
        $r .= self::tab(8) . "protected \$_remote_class_name = '{$class->getName()}';\n";
        $r .= self::tab(4) . "} \n";
        $r .= "} \n";
        return $r;
    }

    private static function tab($n = 1)
    {
        return str_repeat(' ', $n);
    }


}
