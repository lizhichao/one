<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/8/24
 * Time: 下午3:21
 */

namespace One\Swoole;


use One\ConfigTrait;

/**
 * Class Protocol
 * @mixin \swoole_websocket_server
 * @package One\Swoole
 */
class OneServer
{
    use ConfigTrait,bindName;

    const SWOOLE_SERVER = 0;
    const SWOOLE_HTTP_SERVER = 1;
    const SWOOLE_WEBSOCKET_SERVER = 2;

    /**
     * @var \swoole_websocket_server
     */
    private static $_server = null;

    private static $_pro = null;


    private function __construct()
    {

    }

    private function __clone()
    {

    }


    public function __call($name, $arguments)
    {
        if (method_exists(self::$_server, $name)) {
            return self::$_server->$name(...$arguments);
        } else {
            throw new \Exception('方法不存在:' . $name);
        }
    }


    /**
     * 返回全局server
     * @return $this
     */
    public static function getServer()
    {
        if (self::$_pro === null) {
            self::$_pro = new self();
        }
        return self::$_pro;
    }

    public static function getSwooleServer()
    {
        return self::$_server;
    }


    private static function e($str)
    {
        echo $str . "\n";
    }

    public static function runAll()
    {
        if (self::$_server === null) {
            self::_check();
            $server = self::startServer(self::$conf['server']);
            self::addServer($server);
            self::$_server = $server;
            self::e('server start');
            swoole_set_process_name('one_master_'.md5(serialize(self::$conf)));
            $server->start();
        }
    }

    private static function addServer(\swoole_server $server)
    {
        if (!isset(self::$conf['add_listener'])) {
            return false;
        }
        foreach (self::$conf['add_listener'] as $conf) {
            $port = $server->addListener($conf['ip'], $conf['port'], $conf['type']);
            self::e("addListener {$conf['ip']} {$conf['port']}");
            if (isset($conf['set'])) {
                $port->set($conf['set']);
            }
            self::onEvent($port, $conf['action'], $server, $conf);
        }
    }


    private static function startServer($conf)
    {
        $server = null;
        switch ($conf['server_type']) {
            case self::SWOOLE_WEBSOCKET_SERVER:
                $server = new \swoole_websocket_server($conf['ip'], $conf['port'], $conf['mode'], $conf['sock_type']);
                break;
            case self::SWOOLE_HTTP_SERVER:
                $server = new \swoole_http_server($conf['ip'], $conf['port'], $conf['mode'], $conf['sock_type']);
                break;
            case self::SWOOLE_SERVER:
                $server = new \swoole_server($conf['ip'], $conf['port'], $conf['mode'], $conf['sock_type']);
                break;
            default:
                echo "未知的服务类型\n";
                exit;
        }
        $_server_name = [
            self::SWOOLE_WEBSOCKET_SERVER => 'swoole_websocket_server',
            self::SWOOLE_HTTP_SERVER => 'swoole_http_server',
            self::SWOOLE_SERVER => 'swoole_server',
        ];

        self::e("server {$_server_name[$conf['server_type']]} {$conf['ip']} {$conf['port']}");

        if (isset($conf['set'])) {
            $server->set($conf['set']);
        }

        $e = ['close' => 'onClose','workerstart' => 'onWorkerStart','managerstart' => 'onManagerStart'];
        self::onEvent($server, $conf['action'], $server, $conf, $e);

        return $server;
    }


    private static function onEvent($sev, $class, $server, $conf, $call = [])
    {
        $base = [
            Server::class => 1,
            HttpServer::class => 1,
            WebSocket::class => 1
        ];

        $rf = new \ReflectionClass($class);
        $funcs = $rf->getMethods(\ReflectionMethod::IS_PUBLIC);
        $obj = new $class($server, $conf);

        foreach ($funcs as $func) {
            if (!isset($base[$func->class])) {
                if (substr($func->name, 0, 2) == 'on') {
                    $call[strtolower(substr($func->name, 2))] = $func->name;
                }
            }
        }

        if(isset($call['receive'])){
            $call['receive'] = '__receive';
        }

        foreach ($call as $e => $f) {
            $sev->on($e, [$obj, $f]);
        }

    }


    private static function _check()
    {
        if (count(self::$conf) == 0) {
            echo "请配置服务信息\n";
            exit;
        }

        if(!isset(self::$conf['server'])){
            echo "请配置主服务信息\n";
            exit;
        }

        if(isset(self::$conf['add_listener'])){
            $arr = self::$conf['add_listener'];
        }else{
            $arr = [];
        }

        $arr[] = self::$conf['server'];

        $l = count($arr);
        $arr = set_arr_key($arr, 'port');

        if (count($arr) != $l) {
            echo "配置服务信息错误: 端口重复\n";
            exit;
        }

        foreach ($arr as $c) {
            if (!isset($c['action'])) {
                echo "配置服务信息错误: 缺少action\n";
                exit;
            }
        }
    }
}

