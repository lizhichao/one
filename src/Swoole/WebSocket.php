<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/8/24
 * Time: 上午11:17
 */

namespace One\Swoole;


class WebSocket extends HttpServer
{
    /**
     * @var Request
     */
    protected $request;

    /**
     * @var array
     */
    protected $session = [];
    

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {

    }

    public function onHandShake(\swoole_http_request $request, \swoole_http_response $response)
    {
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        $this->request = new Request($request);
        $this->session[$request->fd] = new Session(null, $this->request->cookie(config('session.name')));
        if ($this->onOpen($this->server, $request) === false) {
            $response->end();
            return false;
        }

        $key = base64_encode(sha1($request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
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
}