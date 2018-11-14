<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:32
 */

namespace One\Swoole\Event;

trait TcpEvent
{

    public function onConnect(\swoole_server $server, $fd, $reactor_id)
    {

    }


    public function onReceive(\swoole_server $server, $fd, $reactor_id, $data)
    {


    }

    public function onBufferFull(\swoole_server $server, $fd)
    {


    }

    public function onBufferEmpty(\swoole_server $server, $fd)
    {


    }

}