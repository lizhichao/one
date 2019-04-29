<?php

namespace One\Http;

use One\Exceptions\HttpException;
use One\Facades\Log;
use SebastianBergmann\CodeCoverage\Report\PHP;

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
     * @var \App\Server\AppHttpServer
     */
    protected $server;

    protected $go_id = -1;


    /**
     * Controller constructor.
     * @param $request
     * @param $response
     */
    public function __construct($request, $response, $server = null)
    {
        $this->go_id    = get_co_id();
        $this->request  = $request;
        $this->response = $response;
        $this->server   = $server;
    }

    public function __destruct()
    {
        Log::flushTraceId($this->go_id);
    }

    /**
     * @return Session
     */
    protected function session()
    {
        return $this->response->session();
    }

    /**
     * 异常处理
     * @param $msg
     * @param int $code
     * @throws HttpException
     */
    protected function error($msg, $code = 400)
    {
        throw new HttpException($this->response, $msg, $code);
    }

    /**
     * @param $data
     * @return string
     */
    protected function json($data)
    {
        return format_json($data, 0, $this->request->id());
    }

    /**
     * @param $data
     * @param string $callback
     * @return string
     */
    protected function jsonP($data, $callback = 'callback')
    {
        return $callback . '(' . format_json($data, 0, $this->request->id()) . ')';
    }

    /**
     * 检查必填字段
     * @param array $fields
     * @param array $data
     * @throws HttpException
     */
    protected function verify($fields, $data)
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
    protected function display($tpl, $data = [], $auto_set_tpl_dir = true)
    {
        if ($auto_set_tpl_dir) {
            $dir = substr(get_called_class(), 4);
            $dir = str_replace(['Controllers', 'Controller'], '', $dir);
            $dir = str_replace('\\', '/', $dir);
            $dir = str_replace('//', '/', $dir);
            $dir = strtolower(trim($dir, '/'));
            return $this->response->tpl($dir . '/' . $tpl, $data);
        } else {
            return $this->response->tpl($dir . '/' . $tpl, $data);
        }

    }

}