<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/12
 * Time: 16:32
 * Tcp协议带路由
 * |----|-|...|...|
 * 数据总长度|路由地址长度|路由地址内容|主体内容
 */

namespace One\Protocol;


class TcpRouterData extends ProtocolAbstract
{
    public $url = '';
    public $body = '';
    public $fd = 0;
    public $uuid = '';
    public $session_id = '';

    public $args = [];
    public $class = '';
    public $method = '';
}
