<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/12/4
 * Time: 17:52
 */

namespace One\Swoole {

    class RpcClientTcp
    {
        const RPC_REMOTE_OBJ = '#RpcRemoteObj#';

        private $_need_close = 0;

        private static $_connection = null;

        private static $_is_static = 0;

        protected $_rpc_server = '';

        protected $_remote_class_name = '';

        protected $_token = '';

        protected $_time_out = 1;

        public static $_call_id = '';

        public function __construct(...$args)
        {
            // 这里可以设置为 requestId方便调用链跟踪
            $this->id    = self::$_call_id ? self::$_call_id : $this->uuid();
            $this->calss = $this->_remote_class_name ? $this->_remote_class_name : get_called_class();
            $this->args  = $args;
            if (self::$_connection === null) {
                self::$_connection = $this->connect();
            }
        }

        public function __call($name, $arguments)
        {
            return $this->_callRpc([
                'i' => $this->id, // 分布式唯一id
                'c' => $this->calss, // 调用class
                'f' => $name, // 调用方法名称
                'a' => $arguments, // 调用方法参数
                't' => $this->args, // 构造函数参数 __construct
                's' => self::$_is_static, // 是否是静态方法
                'o' => $this->_token, // token 在中间件可获取
            ]);
        }

        protected function uuid()
        {
            $str = uniqid('', true);
            $arr = explode('.', $str);
            $str = $arr[0] . base_convert($arr[1], 10, 16);
            $len = 32;
            while (strlen($str) <= $len) {
                $str .= bin2hex(openssl_random_pseudo_bytes(4));
            }
            $str = substr($str, 0, $len);
            $str = str_replace(['+', '/', '='], '', base64_encode(hex2bin($str)));
            return $str;
        }

        private function _callRpc($data)
        {
            self::$_is_static = 0;

            $buffer = msgpack_pack($data);
            $buffer = pack('N', 4 + strlen($buffer)) . $buffer;
            $len    = fwrite(self::$_connection, $buffer);
            if ($len !== strlen($buffer)) {
                throw new \Exception('writeToRemote fail', 11);
            }
            $data = msgpack_unpack($this->read());
            if ($data === self::RPC_REMOTE_OBJ) {
                $this->_need_close = 1;
                return $this;
            } else if (is_array($data) && isset($data['err'], $data['msg'])) {
                throw new \Exception($data['msg'], $data['err']);
            } else {
                return $data;
            }
        }

        private function read()
        {
            $all_buffer = '';
            $total_len  = 4;
            $head_read  = false;
            while (1) {
                $buffer = fread(self::$_connection, 8192);
                if ($buffer === '' || $buffer === false) {
                    throw new \Exception('read from remote fail', 2);
                }
                $all_buffer .= $buffer;
                $recv_len   = strlen($all_buffer);
                if ($recv_len >= $total_len) {
                    if ($head_read) {
                        break;
                    }
                    $unpack_data = unpack('Ntotal_length', $all_buffer);
                    $total_len   = $unpack_data['total_length'];
                    if ($recv_len >= $total_len) {
                        break;
                    }
                    $head_read = true;
                }
            }
            return substr($all_buffer, 4);
        }

        private function connect()
        {
            $connection = stream_socket_client($this->_rpc_server, $code, $msg, 3);
            if (!$connection) {
                throw new \Exception($msg,3);
            }
            stream_set_timeout($connection, $this->_time_out);
            return $connection;

        }

        public static function __callStatic($name, $arguments)
        {
            self::$_is_static = 1;
            return (new static)->{$name}(...$arguments);
        }

    }
}
