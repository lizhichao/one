<?php

namespace One\Http;

use One\ConfigTrait;
use One\Exceptions\HttpException;
use One\Facades\Cache;

class Router
{

    use ConfigTrait;

    private static $info = [];

    public static $as_info = [];

    private $args = [];

    public static function clearCache()
    {
        self::$info    = [];
        self::$as_info = [];
    }

    public static function loadRouter()
    {
        if (_CLI_) {
            require self::$conf['path'];
        } else {
            $key = md5(__FILE__ . self::$conf['path'] . filemtime(self::$conf['path']));

            $info          = unserialize(Cache::get($key, function () {
                require self::$conf['path'];
                return serialize([self::$info, self::$as_info]);
            }, 60 * 60 * 24 * 30));
            self::$info    = $info[0];
            self::$as_info = $info[1];
        }
    }

    /**
     * @return string
     */
    private function getKey($method, $uri)
    {
        $paths = explode('/', $uri);
        foreach ($paths as $i => $v) {
            if (is_numeric($v)) {
                $paths[$i] = '#' . $v;
            }
        }
        $path = implode('.', $paths);
        if ($path === '' || $path === '.') {
            $path = '';
        }
        $path = trim($path, '.');
        $path = trim($method . '.' . $path, '.');
        return $path;
    }

    private function matchRouter($key)
    {
        $arr = self::$info;
        if (strpos($key, '.') !== false) {
            $keys = explode('.', $key);
            foreach ($keys as $j => $v) {
                $arr = $this->rules($arr, $v);
                if ($arr === null) {
                    return null;
                }
                if (is_string($arr) || (count($arr) == 1 && isset($arr[0]))) {
                    break;
                }
            }
            $af = array_slice($keys, $j + 1);
            $af = array_map(function ($r) {
                return ltrim($r, '#');
            }, $af);

            $this->args = array_merge($this->args, $af);
            return $arr;
        } else {
            return $this->rules($arr, $key);
        }
    }


    private function rules($arr, $v)
    {
        if (isset($arr[$v])) {
            return $arr[$v];
        }
        $keys = array_keys($arr);
        foreach ($keys as $key) {
            if ($key === 0) {
                continue;
            } else if ($key[0] === '{') {
                $_k = substr($key, 1, -1);
                if (substr($v, 0, 1) == '#') {
                    $v = substr($v, 1);
                }
                if ($_k === 'id') {
                    if (is_numeric($v)) {
                        $this->args[] = $v;
                        return $arr[$key];
                    }
                } else {
                    $this->args[] = $v;
                    return $arr[$key];
                }
            } else if ($key[0] === '`') {
                if (preg_match('/' . substr($key, 1, -1) . '/', $v)) {
                    $this->args[] = $v;
                    return $arr[$key];
                }
            }
        }
        return null;
    }

    private function getAction($method, $uri)
    {
        $info = $this->matchRouter($this->getKey($method, $uri));
        if (!$info) {
            throw new RouterException('Not Found', 404);
        }
        if (is_array($info)) {
            if (isset($info[0])) {
                $info = $info[0];
            } else {
                throw new RouterException('Not Found', 404);
            }
        }
        $fm = [];
        if (is_array($info)) {
            $fm[] = $info;
            if (isset($info['middle'])) {
                $info['middle'] = array_reverse($info['middle']);
                foreach ($info['middle'] as $v) {
                    $fm[] = $v;
                }
            }
        } else {
            $fm[] = $info;
        }
        return $fm;
    }

    public function explain($method, $uri, ...$other_args)
    {
        $info    = $this->getAction($method, $uri);
        $as_name = isset($info[0]['as']) ? $info[0]['as'] : '';

        $str = is_array($info[0]) ? $info[0]['use'] : $info[0];
        list($class, $fun) = explode('@', $str);

        $funcs = [];
        foreach ($info as $i => $v) {
            if ($i > 0) {
                $funcs[] = function ($handler, ...$args) use ($v) {
                    return function () use ($v, $handler, $args) {
                        array_unshift($args, $handler);
                        return call($v, $args);
                    };
                };
            }
        }

        $action = function () use ($class, $fun, $other_args) {
            $obj = new $class(...$other_args);
            if (!method_exists($obj, $fun)) {
                throw new RouterException('method not exists', 404);
            }
            return $obj->$fun(...$this->args);
        };

        return [$class, $fun, $funcs, $action, $this->args, $as_name];

    }


    public function getExecAction($mids, $action, ...$args)
    {
        foreach ($mids as $fn) {
            $action = $fn($action, ...$args);
        }
        return $action;
    }


    private static $group_info      = [];
    private static $max_group_depth = 200;

    /**
     * @param array $rule ['prefix' => '','namespace'=>'','middle'=>[]]
     * @param \Closure $route
     */
    public static function group($rule, $route)
    {
        $len                    = self::$max_group_depth - count(self::$group_info);
        self::$group_info[$len] = $rule;
        ksort(self::$group_info);
        $route();
        unset(self::$group_info[$len]);
    }

    private static function withGroupAction($group_info, $action)
    {
        if (is_array($action)) {
            if (isset($group_info['as']) && isset($action['as'])) {
                $action['as'] = trim($group_info['as'], '.') . '.' . $action['as'];
            }
            if (isset($group_info['namespace'])) {
                $action['use'] = '\\' . $group_info['namespace'] . '\\' . trim($action['use'], '\\');
            }
            if (isset($group_info['middle'])) {
                if (!isset($action['middle'])) {
                    $action['middle'] = [];
                }
                $action['middle'] = array_merge($group_info['middle'], $action['middle']);
            }
        } else {
            if (isset($group_info['namespace'])) {
                $action = '\\' . $group_info['namespace'] . '\\' . trim($action, '\\');
            }
            $action = ['use' => $action, 'middle' => []];
            if (isset($group_info['middle'])) {
                $action['middle'] = array_merge($group_info['middle'], $action['middle']);
            }
        }
        return $action;
    }

    private static function withGroupPath($group_info, $path)
    {
        $path = '/' . trim($path, '/');
        if (isset($group_info['prefix'])) {
            $prefix = trim($group_info['prefix'], '/');
            $path   = '/' . trim($prefix, '/') . $path;
        }
        return $path;
    }


    public static function set($method, $path, $action)
    {
        foreach (self::$group_info as $value) {
            $action = self::withGroupAction($value, $action);
            $path   = self::withGroupPath($value, $path);
        }
        if (is_array($action)) {
            self::createAsInfo($path, $action);
        }
        $arr = explode('/', $method . $path);
        if (is_array($action)) {
            $v = end($arr);
            if ($v !== '') {
                $arr[] = '';
            }
        }
        self::$info = array_merge_recursive(self::$info, self::setPath($arr, $action));
    }


    /**
     * @param $path
     * @param array $action
     */
    private static function createAsInfo($path, $action)
    {
        if (isset($action['as'])) {
            self::$as_info[$action['as']] = rtrim($path, '/');
        }
    }

    private static function setPath($arr, $v, $i = 0)
    {
        if (isset($arr[$i])) {
            if (is_numeric($arr[$i])) {
                $arr[$i] = '#' . $arr[$i];
            } else if ($arr[$i] == '') {
                $arr[$i] = 0;
            }
            return [$arr[$i] => self::setPath($arr, $v, $i + 1)];
        } else {
            return $v;
        }
    }

    /**
     * @param string $path
     * @param string $controller
     */
    public static function controller($path, $controller)
    {
        self::get($path, $controller . '@' . 'getAction');
        self::post($path, $controller . '@' . 'postAction');
        self::put($path, $controller . '@' . 'putAction');
        self::delete($path, $controller . '@' . 'deleteAction');
        self::patch($path, $controller . '@' . 'patchAction');
        self::head($path, $controller . '@' . 'headAction');
        self::options($path, $controller . '@' . 'optionsAction');
    }

    /**
     * @param string $path
     * @param string|array $action
     */
    public static function shell($path, $action)
    {
        self::set('shell', $path, $action);
    }

    /**
     * @param string $path
     * @param string|array $action
     */
    public static function get($path, $action)
    {
        self::set('get', $path, $action);
    }

    /**
     * @param string $path
     * @param string|array $action
     */
    public static function post($path, $action)
    {
        self::set('post', $path, $action);
    }

    /**
     * @param string $path
     * @param string|array $action
     */
    public static function put($path, $action)
    {
        self::set('put', $path, $action);
    }

    /**
     * @param string $path
     * @param string|array $action
     */
    public static function delete($path, $action)
    {
        self::set('delete', $path, $action);
    }

    /**
     * @param string $path
     * @param string|array $action
     */
    public static function patch($path, $action)
    {
        self::set('patch', $path, $action);
    }

    /**
     * @param string $path
     * @param string|array $action
     */
    public static function head($path, $action)
    {
        self::set('head', $path, $action);
    }

    /**
     * @param string $path
     * @param string|array $action
     */
    public static function options($path, $action)
    {
        self::set('options', $path, $action);
    }


}

