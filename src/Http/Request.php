<?php

namespace One\Http;

use One\Facades\Log;

class Request
{

    protected $server = [];

    protected $cookie = [];

    protected $get = [];

    protected $post = [];

    protected $files = [];

    protected $request = [];

    public $fd = 0;

    public $args = [];

    public $class = '';

    public $func = '';
    
    public $as_name = '';

    public function __construct()
    {
        $this->server  = &$_SERVER;
        $this->cookie  = &$_COOKIE;
        $this->get     = &$_GET;
        $this->post    = &$_POST;
        $this->files   = &$_FILES;
        $this->request = &$_REQUEST;
    }

    /**
     * @return string|null
     */
    public function ip($ks = ['REMOTE_ADDR'])
    {
        foreach ($ks as $k){
            $ip = $this->server($k);
            if($ip !== null){
                return $ip;
            }
        }
        return null;
    }


    /**
     * @param $name
     * @return mixed|null
     */
    public function server($name = null, $default = null)
    {
        if ($name === null) {
            return $this->server;
        }
        if (isset($this->server[$name])) {
            return $this->server[$name];
        }
        $name = strtolower($name);
        if (isset($this->server[$name])) {
            return $this->server[$name];
        }
        $name = str_replace('_', '-', $name);
        if (isset($this->server[$name])) {
            return $this->server[$name];
        }
        return $default;
    }

    /**
     * @return mixed|null
     */
    public function userAgent()
    {
        return $this->server('HTTP_USER_AGENT');
    }

    /**
     * @return string
     */
    public function uri()
    {
        $path  = urldecode(array_get_not_null($this->server, ['request_uri', 'REQUEST_URI', 'argv.1']));
        $paths = explode('?', $path);
        return '/' . trim($paths[0], '/');
    }

    /**
     * request unique id
     * @return string
     */
    public function id()
    {
        return Log::getTraceId();
    }


    protected function getFromArr($arr, $key, $default = null)
    {
        if ($key === null) {
            return $arr;
        }
        return array_get($arr, $key, $default);
    }

    /**
     * @param $key
     * @param $default
     * @return mixed|null
     */
    public function get($key = null, $default = null)
    {
        return $this->getFromArr($this->get, $key, $default);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function post($key = null, $default = null)
    {
        return $this->getFromArr($this->post, $key, $default);
    }

    /**
     * @param int $i
     * @return mixed|null
     */
    public function arg($i = null, $default = null)
    {
        global $argv;
        return $this->getFromArr($argv, $i, $default);
    }


    /**
     * @param $key
     * @return mixed|null
     */
    public function res($key = null, $default = null)
    {
        return $this->getFromArr($this->request, $key, $default);
    }


    /**
     * @param $key
     * @return mixed|null
     */
    public function cookie($key = null, $default = null)
    {
        return $this->getFromArr($this->cookie, $key, $default);
    }

    /**
     * @return string
     */
    public function input()
    {
        return file_get_contents('php://input');
    }

    /**
     * @return array
     */
    public function json()
    {
        return json_decode($this->input(), true);
    }

    /**
     * @return array
     */
    public function file()
    {
        $files = [];
        foreach ($this->files as $name => $fs) {
            $keys = array_keys($fs);
            if (is_array($fs[$keys[0]])) {
                foreach ($keys as $k => $v) {
                    foreach ($fs[$v] as $i => $val) {
                        $files[$name][$i][$v] = $val;
                    }
                }
            } else {
                $files[$name] = $fs;
            }
        }
        return $files;
    }

    /**
     * @return string
     */
    public function method()
    {
        return strtolower($this->server('REQUEST_METHOD',''));
    }

    /**
     * @return bool
     */
    public function isJson()
    {
        if ($this->server('HTTP_X_REQUESTED_WITH','') == 'XMLHttpRequest' || strpos($this->server('HTTP_ACCEPT',''), '/json') !== false) {
            return true;
        } else {
            return false;
        }
    }


}