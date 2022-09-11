<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:32
 */

namespace One\Swoole\Event;

use One\Database\Mysql\DbException;
use One\Facades\Log;
use One\Http\Router;
use One\Http\RouterException;
use One\Protocol\TcpRouterData;

trait TcpEvent
{

    public function onConnect(\Swoole\Server $server, $fd, $reactor_id)
    {

    }


    public function __receive(\Swoole\Server $server, $fd, $reactor_id, $data)
    {
        if ($this->protocol) {
            $data = $this->protocol::decode($data);
        }
        $this->onReceive($server, $fd, $reactor_id, $data);
    }


    public function onReceive(\Swoole\Server $server, $fd, $reactor_id, $data)
    {


    }

    public function onBufferFull(\Swoole\Server $server, $fd)
    {


    }

    public function onBufferEmpty(\Swoole\Server $server, $fd)
    {


    }

    /**
     * @param \Swoole\Server $server
     * @param $fd
     * @param $reactor_id
     * @param TcpRouterData $data
     */
    protected function tcpRouter(\Swoole\Server $server, $fd, $reactor_id, $data)
    {
        $data->fd   = $fd;
        try {
            $router = new Router();
            $server = $this instanceof Server ? $this : $this->server;
            list($data->class, $data->method, $mids, $action, $data->args) = $router->explain('tcp', $data->url, $data, $server);
            $f   = $router->getExecAction($mids, $action, $data, $server);
            $res = $f();
        } catch (RouterException $e) {
            $res = $e->getMessage();
        } catch (\Throwable $e) {
            $res = $e->getMessage();
            if ($e instanceof DbException) {
                $res = 'db error!';
            }
            error_report($e);
        }
        if ($res) {
            $server->send($fd, $res);
        }

    }

}