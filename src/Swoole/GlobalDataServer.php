<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/10/12
 * Time: 下午9:37
 */

namespace One\Swoole;

class GlobalDataServer extends Server
{
    /**
     * @var GlobalData
     */
    private $global = null;

    public function __construct(\swoole_server $server, array $conf)
    {
        parent::__construct($server, $conf);
        $this->global = new GlobalData();
    }

    public function onReceive(\swoole_server $server, $fd, $reactor_id, $data)
    {
        $ar = unserialize($data);
        if (method_exists($this->global, $ar['m'])) {
            $ret = $this->global->{$ar['m']}(...$ar['args']);
            if (strpos($ar['m'], 'get') !== false) {
                $this->send($fd, serialize($ret));
            }
        } else {
            echo "warn method {$ar['m']} not exist\n";
        }
    }

    public function onWorkerStart(\swoole_server $server, $worker_id)
    {

    }

}