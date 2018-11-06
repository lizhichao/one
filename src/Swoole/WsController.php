<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/9
 * Time: 11:05
 */

namespace One\Swoole;


use App\Protocol\AppWebSocket;
use One\Facades\Log;

class WsController
{
    /**
     * @var \swoole_websocket_frame
     */
    protected $frame;

    /**
     * @var AppWebSocket
     */
    protected $server;

    public function __construct(\swoole_websocket_frame $frame, $server)
    {
        $this->frame = $frame;
        $this->server = $server;
    }

    public function __destruct()
    {
        Log::flushTraceId();
    }

    /**
     * @return Session
     */
    final protected function session()
    {
        return $this->request->session();
    }

    /**
     * @return Protocol
     */
    final protected function server()
    {
        return Protocol::getServer();
    }

}