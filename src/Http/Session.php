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
        session_name(config('session.name'));
        $time = intval(ini_get('session.gc_maxlifetime'));
        if (config('session.drive') == 'redis') {
            session_set_save_handler(new \One\Cache\SessionHandler($time), true);
        }
        if ($id) {
            session_id($id);
        }
        session_start();
        setcookie(session_name(), session_id(), time() + $time, '/');
        $this->data = $_SESSION;
    }

    public function getId()
    {
        return session_id();
    }

    public function set($key, $val)
    {
        $this->data[$key] = $val;
    }

    public function get($key = null)
    {
        if ($key) {
            return array_get($this->data, $key);
        } else {
            return $this->data;
        }
    }

    public function del($key)
    {
        unset($this->data[$key]);
    }

    public function __destruct()
    {
        $_SESSION = $this->data;
    }
}