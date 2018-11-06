<?php

namespace One\Cache;

use One\Facades\Cache;

class SessionHandler implements \SessionHandlerInterface{

    private $prefix = 'vic_sn_' ;

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
        return Cache::del($this->prefix .$session_id);
    }

    public function gc($maxlifetime)
    {
        return true;
    }

    public function open($save_path, $name)
    {
        return true;
    }

    public function read($session_id)
    {
        return Cache::get($this->prefix.$session_id);
    }

    public function write($session_id, $session_data)
    {
        return Cache::setex($this->prefix.$session_id,$this->expire_time,$session_data);

    }

}
