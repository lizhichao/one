<?php

namespace One\Http;

use App\Protocol\AppHttpServer;
use One\Exceptions\HttpException;

class Controller
{

    /**
     * @var Request
     */
    protected $request = null;

    /**
     * @var \One\Swoole\Response
     */
    protected $response = null;

    /**
     * @var AppHttpServer
     */
    protected $server;


    /**
     * Controller constructor.
     * @param $request
     * @param $response
     */
    public function __construct($request, $response, $server = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->server = $server;

    }

    public function __destruct()
    {

    }

    /**
     * @return Session
     */
    final protected function session()
    {
        return $this->response->session();
    }

    /**
     * 异常处理
     * @param $msg
     * @param int $code
     * @throws HttpException
     */
    final protected function error($msg, $code = 400)
    {
        throw new HttpException($this->response, $msg, $code);
    }

    /**
     * @param $data
     * @return string
     */
    final protected function json($data)
    {
        return format_json($data, 0, $this->request->id());
    }

    /**
     * @param $data
     * @param string $callback
     * @return string
     */
    final protected function jsonP($data, $callback = 'callback')
    {
        return $callback . '(' . format_json($data, 0, $this->request->id()) . ')';
    }

    /**
     * 检查必填字段
     * @param array $fields
     * @param array $data
     * @throws HttpException
     */
    final protected function verify($fields, $data)
    {
        foreach ($fields as $v) {
            $val = array_get($data, $v);
            if ($val === null || $val == '') {
                $this->error("{$v}不能为空");
            }
        }
    }

    /**
     * 模板渲染
     * @param string $tpl 模板
     * @param array $data
     * @return string
     * @throws HttpException
     */
    final protected function display($tpl, $data = [])
    {
        $dir = strtolower(substr(get_called_class(), 16, -10));
        return $this->response->tpl($dir . '/' . $tpl, $data);
    }

}