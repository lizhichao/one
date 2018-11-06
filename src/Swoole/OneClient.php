<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/22
 * Time: 15:48
 */

namespace One\Swoole;

use One\ConfigTrait;

class OneClient
{
    use ConfigTrait;

    /**
     * @return AsyncClient
     */
    public static function start()
    {
        return self::client(self::$conf);
    }

    private static function client($conf)
    {
        if ($conf['async']) {
            $client = new \swoole_client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);
        } else {
            $client = new \swoole_client(SWOOLE_TCP, SWOOLE_SOCK_SYNC);
        }

        if (isset($conf['set'])) {
            $client->set($conf['set']);
        }

        if ($conf['async']) {
            $one_cli = self::onEvent($client, $conf['action'], $conf);
            $client->connect(self::$conf['ip'], self::$conf['port'], self::$conf['time']);
            return $one_cli;
        } else {
            @$client->connect(self::$conf['ip'], self::$conf['port'], self::$conf['time']);
            $obj = new $conf['action']($client, $conf);
            if($client->isConnected()){
                $obj->connected = 1;
                echo "connect {$conf['ip']}:{$conf['port']} success\n";
            }
            return $obj;
        }
    }

    private static function onEvent($client, $class, $conf)
    {
        $call = [];
        $rf = new \ReflectionClass($class);
        $funcs = $rf->getMethods(\ReflectionMethod::IS_PUBLIC);
        $obj = new $class($client, $conf);

        foreach ($funcs as $func) {
            if (substr($func->name, 0, 2) == 'on') {
                $call[strtolower(substr($func->name, 2))] = $func->name;
            }
        }

        if (isset($call['receive'])) {
            $call['receive'] = '__receive';
        }

        foreach ($call as $e => $f) {
            $client->on($e, [$obj, $f]);
        }

        return $obj;

    }


}