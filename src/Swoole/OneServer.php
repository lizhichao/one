<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/8/24
 * Time: 下午3:21
 */

namespace One\Swoole;


use One\ConfigTrait;
use One\Swoole\Server\HttpServer;
use One\Swoole\Server\UdpServer;
use One\Swoole\Server\WsServer;

/**
 * Class Protocol
 * @mixin \swoole_websocket_server
 * @package One\Swoole
 */
class OneServer
{
    use ConfigTrait;

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

    /**
     * 返回全局server
     * @return Server
     */
    public static function getServer()
    {
        return self::$_server;
    }


    private static function e($str)
    {
        echo $str . "\n";
    }

    private static function getPid($k)
    {
        $name = 'one_master_' . md5(serialize(self::$conf));
        $str  = exec("ps -ef | grep {$name} | grep -v grep");
        $arr  = explode(' ', $str);
        $arr  = array_filter($arr, 'trim');
        $arr  = array_values($arr);
        if (empty($arr) && ($k === 'reload' || $k === 'stop')) {
            exit("程序未运行\n");
        }
        return $arr[1];
    }

    private static function shell()
    {
        global $argv;
        $k    = trim(end($argv));
        $name = 'one_master_' . md5(serialize(self::$conf));
        if ($k === 'reload') {
            $id = self::getPid('reload');
            exec("kill -USR1 {$id}");
            exit("reload succ\n");
        } else if ($k === 'stop') {
            $id = self::getPid('stop');
            exec("kill {$id}");
            exit("stop succ\n");
        }

    }

    public static function runAll()
    {
        self::shell();
        if (self::$_server === null) {
            self::_check();
            list($swoole, $server) = self::startServer(self::$conf['server']);
            self::addServer($swoole, $server);
            self::$_server = $server;
            self::e('server start');
            @swoole_set_process_name('one_master_' . md5(serialize(self::$conf)));
            $server->start();
        }
    }

    private static function addServer(\swoole_server $swoole, $server)
    {
        if (!isset(self::$conf['add_listener'])) {
            return false;
        }
        foreach (self::$conf['add_listener'] as $conf) {
            $port = $swoole->addListener($conf['ip'], $conf['port'], $conf['type']);
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
            self::SWOOLE_HTTP_SERVER      => 'swoole_http_server',
            self::SWOOLE_SERVER           => 'swoole_server',
        ];

        self::e("server {$_server_name[$conf['server_type']]} {$conf['ip']} {$conf['port']}");

        if (isset($conf['set'])) {
            $server->set($conf['set']);
        }

        $e = ['workerstart' => 'onWorkerStart', 'managerstart' => 'onManagerStart'];

        $obj = self::onEvent($server, $conf['action'], $server, $conf, $e);

        return [$server, $obj];
    }


    private static function onEvent($sev, $class, $server, $conf, $call = [])
    {
        $rf    = new \ReflectionClass($class);
        $funcs = $rf->getMethods(\ReflectionMethod::IS_PUBLIC);
        $obj   = new $class($server, $conf);

        foreach ($funcs as $func) {
            if (strpos($func->class, 'One\\Swoole\\') === false) {
                if (substr($func->name, 0, 2) == 'on') {
                    $call[strtolower(substr($func->name, 2))] = $func->name;
                }
            }
        }

        if (isset($call['receive'])) {
            $call['receive'] = '__receive';
        }
        
        foreach ($call as $e => $f) {
            $sev->on($e, [$obj, $f]);
        }

        return $obj;

    }


    private static function _check()
    {
        if (count(self::$conf) == 0) {
            echo "请配置服务信息\n";
            exit;
        }

        if (!isset(self::$conf['server'])) {
            echo "请配置主服务信息\n";
            exit;
        }

        if (isset(self::$conf['add_listener'])) {
            $arr = self::$conf['add_listener'];
        } else {
            $arr = [];
        }

        $arr[] = self::$conf['server'];

        $l   = count($arr);
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

