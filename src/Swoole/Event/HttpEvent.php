<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:32
 */

namespace One\Swoole\Event;

use App\Exceptions\Handler;
use One\Exceptions\HttpException;
use One\Facades\Log;
use One\Http\Router;
use One\Http\RouterException;

trait HttpEvent
{
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {

    }

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    protected function httpRouter(\swoole_http_request $request, \swoole_http_response $response)
    {
        $req = new \One\Swoole\Request($request);
        Log::setTraceId($req->id());
        $res = new \One\Swoole\Response($req, $response);
        try {
            $router = new Router();
            list($req->class, $req->method, $mids, $action, $req->args) = $router->explain($req->method(), $req->uri(), $req, $res, $this);
            $f = $router->getExecAction($mids, $action, $res, $this);
            $data = $f();
        } catch (\One\Exceptions\HttpException $e) {
            $data = Handler::render($e);
        } catch (RouterException $e) {
            $data = Handler::render(new HttpException($res, $e->getMessage(), $e->getCode()));
        } catch (\Throwable $e) {
            $data = $e->getMessage();
        }
        $response->exist = $this->server->exist($request->fd);
        if ($data && $response->exist) {
            $response->write($data);
        }

    }
}