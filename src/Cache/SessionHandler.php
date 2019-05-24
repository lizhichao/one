<?php

namespace One\Cache;

use One\Facades\Redis as FacadesRedis;

class SessionHandler implements \SessionHandlerInterface
{

    private $prefix = 'vic_sn_';

    private $expire_time = 1200;

    /**
     * Session constructor.
     * @param int $expire_time 过期时间
     */
    public function __construct($expire_time)
    {
        $this->expire_time = $expire_time;
    }

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        return FacadesRedis::del($this->prefix . $session_id) ? true : false;
    }

    public function gc($maxlifetime)
    {

        return 0;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function read($session_id)
    {
        return (string)@FacadesRedis::get($this->prefix . $session_id);
    }

    public function write($session_id, $session_data)
    {
        return FacadesRedis::set($this->prefix . $session_id, $session_data, $this->expire_time);

    }

}
