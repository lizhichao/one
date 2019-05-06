<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:30
 */

namespace One\Swoole\Server;


use One\Swoole\Event\TaskEvent;
use One\Swoole\Event\TcpEvent;
use One\Swoole\Server;

class TcpServer extends Server
{
    use TcpEvent,TaskEvent;
}