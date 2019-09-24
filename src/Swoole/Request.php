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
        $this->server = &$request->server;
        foreach ($request->header as $k => &$v) {
            $this->server['http-' . $k] = &$v;
        }
        $this->fd          = $request->fd;
        $this->httpRequest = $request;

        if ($request->cookie === null) {
            $this->cookie = [];
        } else {
            $this->cookie = &$request->cookie;
        }
        if ($request->get === null) {
            $this->get = [];
        } else {
            $this->get = &$request->get;
        }
        if ($request->post === null) {
            $this->post = [];
        } else {
            $this->post = &$request->post;
        }
        if ($request->files === null) {
            $this->files = [];
        } else {
            $this->files = &$request->files;
        }
        $this->request = $this->post + $this->get;
        $this->id      = uuid();
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

    public function file()
    {
        return $this->files;
    }

}