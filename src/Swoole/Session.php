<?php

namespace One\Swoole;


use One\Cache\File;
use One\Cache\Redis;

class Session
{
    private $data = [];

    private $name = '';

    private $session_id = '';

    private $time = 0;

    private $drive;

    private $prefix = 'session_';

    /**
     * Session constructor.
     * @param Response $response
     * @param null $id session.id
     */
    public function __construct($response = null, $id = null)
    {
        $config = config('session');
        if (isset($config['fn_sid'])) {
            $id = $config['fn_sid']($response);
            unset($config['fn_sid']);
        }

        $this->name = $config['name'];

        if ($id && preg_match('/^\w+$/', $id)) {
            $this->session_id = $id;
        } else if ($response) {
            $this->session_id = $response->getHttpRequest()->cookie($this->name);
            if (!$this->session_id) {
                $this->session_id = sha1(uuid());
            }
        }

        if (!$this->session_id) {
            return;
        }

        if (!isset($config['path'])) {
            $config['path'] = '/';
        }
        if (!isset($config['lifetime'])) {
            $config['lifetime'] = intval(ini_get('session.gc_maxlifetime'));
        }
        $this->time = $config['lifetime'];

        if ($config['drive'] == 'redis') {
            $this->drive = new Redis();
        } else {
            $this->drive = new File();
        }

        // secure，httponly 以及 samesite
        //$secure = null, $httponly = null, $samesite = null, $priority = null
        $args = [
            time() + $this->time, $config['path'], $config['domain'],
            null, null, null
        ];

        if (isset($config['secure'])) {
            $args[3] = $config['secure'];
        }

        if (isset($config['httponly'])) {
            $args[4] = $config['httponly'];
        }
        if (isset($config['samesite'])) {
            $args[5] = $config['samesite'];
        }

        if ($response) {
            $response->cookie($this->name, $this->session_id, $args);
        }

        $this->data = unserialize($this->drive->get($this->prefix . $this->session_id));
    }

    public function getId()
    {
        return $this->session_id;
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

    public function del($key)
    {
        unset($this->data[$key]);
    }


    public function __destruct()
    {
        if ($this->session_id) {
            $this->drive->set($this->prefix . $this->session_id, serialize($this->data), $this->time);
        }
    }

}