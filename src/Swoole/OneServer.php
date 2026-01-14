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
 * @mixin \Swoole\WebSocket\Server
 * @package One\Swoole
 */
class OneServer
{
    use ConfigTrait;

    const SWOOLE_SERVER           = 0;
    const SWOOLE_HTTP_SERVER      = 1;
    const SWOOLE_WEBSOCKET_SERVER = 2;

    /**
     * @var \Swoole\WebSocket\Server
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

    public static function parseArgv()
    {
        $k = array_get(getopt('o:'), 'o');
        if ($k === 'start') {
            self::$conf['server']['set']['daemonize'] = 1;
            return;
        }
        if ($k !== 'reload' && $k !== 'stop') {
            if (!isset(self::$conf['server']['set']['pid_file'])) {
                return;
            }
            $dir = dirname(self::$conf['server']['set']['pid_file']);
            if (!is_dir($dir) && !mkdir($dir)) {
                exit("创建文件夹: {$dir} 失败 ， 请检查权限 \n");
            }
            return;
        }
        if (!isset(self::$conf['server']['set']['pid_file'])) {
            exit("未配置 server.set.pid_file 路径！\n");
        }
        $file = self::$conf['server']['set']['pid_file'];
        $id   = intval(trim(file_get_contents($file)));
        if (!$id) {
            exit("pid不正确\n");
        }
        if ($k === 'reload') {
            exec("kill -USR1 {$id}");
            exit("reload succ\n");
        } else if ($k === 'stop') {
            exec("kill {$id}");
            exit("stop succ\n");
        }
    }

    public static function runAll()
    {
        if (self::$_server === null) {
            self::_check();
            list($swoole, $server) = self::startServer(self::$conf['server']);
            self::addServer($swoole, $server);
            self::$_server = $server;
            self::e('server start');
            self::e('one version : ' . _ONE_V_);
            @$swoole->p_name = array_get(self::$conf, 'server.name', basename(dirname(_APP_PATH_)));
            @swoole_set_process_name('one_master_' . $swoole->p_name);
            $server->start();
        }
    }

    private static function addServer(\Swoole\Server $swoole, $server)
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
                $server = new \Swoole\WebSocket\Server($conf['ip'], $conf['port'], $conf['mode'], $conf['sock_type']);
                break;
            case self::SWOOLE_HTTP_SERVER:
                $server = new \Swoole\Http\Server($conf['ip'], $conf['port'], $conf['mode'], $conf['sock_type']);
                break;
            case self::SWOOLE_SERVER:
                $server = new \Swoole\Server($conf['ip'], $conf['port'], $conf['mode'], $conf['sock_type']);
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


    /**
     * @throws \ReflectionException
     */
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

