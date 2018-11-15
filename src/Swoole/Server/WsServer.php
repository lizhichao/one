<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/8/24
 * Time: 上午11:17
 */

namespace One\Swoole\Server;

use One\Swoole\Event\HttpEvent;
use One\Swoole\Event\WsEvent;
use One\Swoole\Server;

class WsServer extends Server
{
    use WsEvent;

    /**
     * @var array
     */
    protected $session = [];

}