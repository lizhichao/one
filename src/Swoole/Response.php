<?php

namespace One\Swoole;

use One\Facades\Log;

/**
 * Class Response
 * @package One\Swoole
 * @mixin \Swoole\Http\Response
 */
class Response extends \One\Http\Response
{

    /**
     * @var \Swoole\Http\Response
     */
    private $httpResponse;

    /**
     * @var \Swoole\Http\Request
     */
    protected $httpRequest;

    public function __construct(Request $request, \Swoole\Http\Response $response)
    {
        $this->httpResponse = $response;
        $this->httpRequest  = $request;
    }

    public function header($key, $val, $replace = false, $code = null)
    {
        $this->httpResponse->header($key, $val, $replace);
        if ($code) {
            $this->code($code);
        }
    }

    /**
     * @param $code
     * @return $this|\One\Http\Response
     */
    public function code($code)
    {
        $this->httpResponse->status($code);
        return $this;
    }

    public function cookie(...$args)
    {
        if (is_array($args[2])) {
            $this->httpResponse->cookie($args[0], $args[1], ...$args[2]);
        } else {
            $this->httpResponse->cookie(...$args);
        }

    }

    public function write($html)
    {
        $this->httpResponse->write($html);
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->httpResponse, $name)) {
            return $this->httpResponse->$name(...$arguments);
        }
    }

}