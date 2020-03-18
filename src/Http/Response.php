<?php

namespace One\Http;

use One\Exceptions\HttpException;

class Response
{

    /**
     * @var Request
     */
    protected $httpRequest;

    protected $_session = null;

    public $_auto_to_json = true;


    public function __construct(Request $request)
    {
        $this->httpRequest = $request;
    }


    public function getHttpRequest()
    {
        return $this->httpRequest;
    }

    /**
     * @return Session
     */
    public function session()
    {
        if (!$this->_session) {
            if (_CLI_) {
                $this->_session = new \One\Swoole\Session($this);
            } else {
                $this->_session = new \One\Http\Session($this);
            }
        }
        return $this->_session;
    }

    /**
     * @return bool
     */
    public function cookie()
    {
        return setcookie(...func_get_args());
    }

    public function write($html)
    {
        echo $html;
    }


    /**
     * @param mixed $data
     * @param int $code
     * @param null|string $callback
     * @return string
     */
    public function json($data, $code = 0, $callback = null)
    {
        $this->header('Content-type', 'application/json');
        if ($callback === null) {
            return format_json($data, $code, $this->httpRequest->id());
        } else {
            return $callback . '(' . format_json($data, $code, $this->httpRequest->id()) . ')';
        }
    }

    public function header($key, $val, $replace = false, $code = null)
    {
        header($key . ':' . $val, $replace, $code);
    }

    private $status_texts = array(
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',            // RFC2518
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',          // RFC4918
        208 => 'Already Reported',      // RFC5842
        226 => 'IM Used',               // RFC3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',    // RFC7238
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',                                               // RFC2324
        421 => 'Misdirected Request',                                         // RFC7540
        422 => 'Unprocessable Entity',                                        // RFC4918
        423 => 'Locked',                                                      // RFC4918
        424 => 'Failed Dependency',                                           // RFC4918
        425 => 'Reserved for WebDAV advanced collections expired proposal',   // RFC2817
        426 => 'Upgrade Required',                                            // RFC2817
        428 => 'Precondition Required',                                       // RFC6585
        429 => 'Too Many Requests',                                           // RFC6585
        431 => 'Request Header Fields Too Large',                             // RFC6585
        451 => 'Unavailable For Legal Reasons',                               // RFC7725
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',                                     // RFC2295
        507 => 'Insufficient Storage',                                        // RFC4918
        508 => 'Loop Detected',                                               // RFC5842
        510 => 'Not Extended',                                                // RFC2774
        511 => 'Network Authentication Required',                             // RFC6585
    );

    /**
     * @param $code
     * @return $this
     */
    public function code($code)
    {
        if (isset($this->status_texts[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $this->status_texts[$code]);
        }
        return $this;
    }


    /**
     * 页面跳转
     * @param $url
     * @param array $args
     */
    public function redirect($url, $args = [])
    {
        if (isset($args['time'])) {
            $this->header('Refresh', $args['time'] . ';url=' . $url);
        } else if (isset($args['httpCode'])) {
            $this->header('Location', $url, true, $args['httpCode']);
        } else {
            $this->header('Location', $url, true, 302);
        }
        return '';
    }


    /**
     * @param string $file
     * @param array $data
     * @return string
     * @throws HttpException
     */
    public function tpl($file, array $data = [])
    {
        if ($this->_auto_to_json && $this->getHttpRequest()->isJson()) {
            $this->header('Content-type', 'application/json');
            return format_json($data, 0, $this->getHttpRequest()->id());
        } else {
            if (defined('_APP_PATH_VIEW_') === false) {
                throw new HttpException($this, '未定义模板路径:_APP_PATH_VIEW_', 4001);
            }
            $file = _APP_PATH_VIEW_ . '/' . $file . '.php';
            if (!file_exists($file)) {
                throw new HttpException($this, '未定义模板路径:' . $file, 4002);
            }
            ob_start();
            extract($data);
            require $file;
            return ob_get_clean();
        }
    }

}