<?php

namespace One\Swoole;


class Request extends \One\Http\Request
{

    private $id = 'Request_id';

    /**
     * @var \swoole_http_request
     */
    private $httpRequest;

    public function __construct(\swoole_http_request $request)
    {
        foreach ($request->server as $k => $v){
            $this->server[str_replace('-','_',strtoupper($k))] = $v;
        }
        foreach ($request->header as $k => $v){
            $this->server['HTTP_'.str_replace('-','_',strtoupper($k))] = $v;
        }
        $this->cookie = &$request->cookie;
        $this->get = &$request->get;
        $this->post = &$request->post;
        $this->files = &$request->files;
        $this->httpRequest = $request;
        $this->post = $this->post??[];
        $this->get = $this->get??[];
        $this->cookie = $this->cookie??[];
        $this->request = $this->post + $this->get;
        $this->id = uuid();
    }

    /**
     * request unique id
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function res($key = null, $default = null)
    {
        return $this->getFromArr($this->get + $this->post, $key, $default);
    }

    /**
     * @return string
     */
    public function input()
    {
        return $this->httpRequest->rawContent();
    }

}