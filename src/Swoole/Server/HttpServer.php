<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/8/24
 * Time: 上午11:16
 */

namespace One\Swoole\Server;


use One\Swoole\Event\HttpEvent;
use One\Swoole\Server;

class HttpServer extends Server
{
    use HttpEvent;
}