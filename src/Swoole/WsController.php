<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/9
 * Time: 11:05
 */

namespace One\Swoole;

use One\Facades\Log;

class WsController
{
    /**
     * @var \swoole_websocket_frame
     */
    protected $frame;

    /**
     * @var WebSocket
     */
    protected $server;

    /**
     * @var Session
     */
    protected $session;

    protected $go_id;

    public function __construct($frame, $server, $session = null)
    {
        $this->go_id   = get_co_id();
        $this->frame   = $frame;
        $this->server  = $server;
        $this->session = $session;
    }

}