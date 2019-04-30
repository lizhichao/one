<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:32
 */

namespace One\Swoole\Event;

use One\Facades\Log;
use One\Http\Router;
use One\Http\RouterException;
use One\Swoole\Request;
use One\Swoole\Session;

trait WsEvent
{
    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {

    }

    public function onHandShake(\swoole_http_request $request, \swoole_http_response $response)
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

    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
        return true;
    }

    /**
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     * @return bool
     */
    protected function wsRouter(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        $info = json_decode($frame->data, true);
        if (!$info || !isset($info['u']) || !isset($info['d'])) {
            $this->push($frame->fd, '格式错误');
            return false;
        }
        $frame->data = $info['d'];
        $frame->uuid = uuid();
        Log::setTraceId($frame->uuid);
        try {
            $router  = new Router();
            $server = $this instanceof Server ? $this : $this->server;
            $session = isset($this->session[$frame->fd]) ? $this->session[$frame->fd] : null;
            list($frame->class, $frame->method, $mids, $action, $frame->args) = $router->explain('ws', $info['u'], $frame, $server, $session);
            $f    = $router->getExecAction($mids, $action, $frame, $server, $session);
            $data = $f();
        } catch (RouterException $e) {
            $data = $e->getMessage();
        } catch (\Throwable $e) {
            $data = $e->getMessage();
            error_report($e);
        }

        if ($data) {
            $server->push($frame->fd, $data);
        }

    }

}