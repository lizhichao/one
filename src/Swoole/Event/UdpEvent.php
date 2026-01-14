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
    public function onPacket(\Swoole\Server $server, $data, array $client_info)
    {

    }

    public function onReceive(\Swoole\Server $server, $fd, $reactor_id, $data)
    {

    }

}