<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/8/24
 * Time: 上午11:16
 */

namespace One\Swoole;


class HttpServer extends Server
{
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        $response->header('Content-type', 'text/html; charset=utf-8');
        $response->end('请设置 onRequest 方法');
    }
}