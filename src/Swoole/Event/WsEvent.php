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
use One\Swoole\Request;
use One\Swoole\Session;

trait WsEvent
{
    public function onMessage(\Swoole\Websocket\Server $server, \Swoole\Websocket\Frame $frame)
    {

    }

    public function onHandShake(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten          = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        $req                         = new Request($request);
        $this->session[$request->fd] = new Session(null, $req->cookie(config('session.name')));
        if ($this->onOpen($this->server, $request) === false) {
            $response->end();
            return false;
        }

        $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $headers = [
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-WebSocket-Accept'  => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $response->end();
        return true;
    }

    public function onOpen(\Swoole\Websocket\Server $server, \Swoole\Http\Request $request)
    {
        return true;
    }

    /**
     * @param \Swoole\Websocket\Server $server
     * @param \Swoole\Websocket\Frame $frame
     * @return bool
     */
    protected function wsRouter(\Swoole\Websocket\Server $server, \Swoole\Websocket\Frame $frame)
    {
        $info = json_decode($frame->data, true);
        if (!$info || !isset($info['u']) || !isset($info['d'])) {
            $this->push($frame->fd, '格式错误');
            return false;
        }
        $frame->data = $info['d'];
        try {
            $router  = new Router();
            $server  = $this instanceof Server ? $this : $this->server;
            $session = isset($this->session[$frame->fd]) ? $this->session[$frame->fd] : null;
            list($frame->class, $frame->func, $mids, $action, $frame->args, $frame->as_name) = $router->explain('ws', $info['u'], $frame, $server, $session);
            $f    = $router->getExecAction($mids, $action, $frame, $server, $session);
            $data = $f();
        } catch (RouterException $e) {
            $data = $e->getMessage();
        } catch (\Throwable $e) {
            $data = $e->getMessage();
            if ($e instanceof DbException) {
                $data = 'db error!';
            }
            error_report($e);
        }
        if ($data) {
            $server->push($frame->fd, $data);
        }

    }

}