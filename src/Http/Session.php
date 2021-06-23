<?php

namespace One\Http;


class Session
{
    private $data = [];

    /**
     * Session constructor.
     * @param null $response
     * @param null $id session.id
     */
    public function __construct($response = null, $id = null)
    {
        $config = config('session');
        if (isset($config['fn_sid'])) {
            $id = $config['fn_sid']($response);
            unset($config['fn_sid']);
        }
        if (!isset($config['path'])) {
            $config['path'] = '/';
        }
        session_name($config['name']);
        unset($config['name']);
        if (isset($config['lifetime'])) {
            $time = $config['lifetime'];
        } else {
            $time = intval(ini_get('session.gc_maxlifetime'));
        }
        if ($config['drive'] == 'redis') {
            session_set_save_handler(new \One\Cache\SessionHandler($time), true);
        }
        unset($config['drive']);
        if ($id) {
            session_id($id);
        }
        if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
            session_set_cookie_params($config);
        } else {
            session_set_cookie_params($time, $config['path'], $config['domain']);
        }
        session_start();
        $this->data = &$_SESSION;
    }

    public function getId()
    {
        return session_id();
    }

    public function set($key, $val)
    {
        $this->data[$key] = $val;
    }

    public function get($key = null, $default = null)
    {
        if ($key) {
            return array_get($this->data, $key, $default);
        } else {
            return $this->data;
        }
    }

    public function del($key = null)
    {
        if ($key) {
            unset($this->data[$key]);
        } else {
            $this->data = [];
        }

    }

    public function __destruct()
    {
        $_SESSION = $this->data;
    }
}