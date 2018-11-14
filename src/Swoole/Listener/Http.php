<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:58
 */

namespace One\Swoole\Listener;


use One\Swoole\Event\HttpEvent;

class Http extends Port
{
    use HttpEvent;
}