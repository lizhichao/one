<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:57
 */

namespace One\Swoole\Listener;

use One\Swoole\Event\WsEvent;

class Ws extends Port
{
    use WsEvent;

    /**
     * @var array
     */
    protected $session = [];


}