<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:32
 */

namespace One\Swoole\Event;

trait UdpEvent
{
    public function onPacket(\swoole_server $server, $data, array $client_info)
    {

    }

    public function onReceive(\swoole_server $server, $fd, $reactor_id, $data)
    {

    }

}