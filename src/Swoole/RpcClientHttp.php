<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/4
 * Time: 17:52
 */

namespace One\Swoole {

    class RpcClientHttp
    {
        const RPC_REMOTE_OBJ = '#RpcRemoteObj#';

        private $_need_close = 0;

        private static $_is_static = 0;

        protected $_rpc_server = '';

        protected $_remote_class_name = '';

        public static $_call_id = '';

        public function __construct(...$args)
        {
            // 这里可以设置为 requestId方便调用链跟踪
            $this->id    = self::$_call_id ? self::$_call_id : $this->uuid();
            $this->calss = $this->_remote_class_name ? $this->_remote_class_name : get_called_class();
            $this->args  = $args;
        }

        public function __call($name, $arguments)
        {
            return $this->_callRpc([
                'i' => $this->id,
                'c' => $this->calss,
                'f' => $name,
                'a' => $arguments,
                't' => $this->args,
                's' => self::$_is_static
            ]);
        }

        private function uuid()
        {
            $str = uniqid('', true);
            $arr = explode('.', $str);
            $str = $arr[0] . base_convert($arr[1], 10, 16);
            $len = 32;
            while (strlen($str) <= $len) {
                $str .= bin2hex(base_convert(mt_rand(10000, 99999), 10, 16));
            }
            $str = substr($str, 0, $len);
            $str = str_replace(['+', '/', '='], '', base64_encode(hex2bin($str)));
            return $str;
        }

        private function _callRpc($data)
        {
            self::$_is_static = 0;

            $opts    = ['http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/rpc',
                'content' => msgpack_pack($data)
            ]];
            $context = stream_context_create($opts);
            $result  = file_get_contents($this->_rpc_server, false, $context);
            $data    = msgpack_unpack($result);
            if ($data === self::RPC_REMOTE_OBJ) {
                $this->_need_close = 1;
                return $this;
            } else if (is_array($data) && isset($data['err'], $data['msg'])) {
                throw new \Exception($data['msg'], $data['err']);
            } else {
                return $data;
            }
        }

        public static function __callStatic($name, $arguments)
        {
            self::$_is_static = 1;
            return (new static)->{$name}(...$arguments);
        }

        public function __destruct()
        {
            if ($this->_need_close) {
                $this->_callRpc(['i' => $this->id]);
            }
        }

    }
}
