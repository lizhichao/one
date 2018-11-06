<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/18
 * Time: 14:19
 */

namespace One\Swoole;


use One\Protocol\ProtocolAbstract;

abstract class AsyncClient
{
    /**
     * @var \swoole_client
     */
    protected $cli;

    protected $conf = [];

    public $connected = 0;

    /**
     * @var ProtocolAbstract
     */
    protected $protocol;

    public function __construct(\swoole_client $cli, array $conf)
    {
        $this->cli = $cli;
        $this->conf = $conf;
        if (isset($conf['protocol'])) {
            $this->protocol = $conf['protocol'];
        }
    }

    public function onConnect(\swoole_client $cli)
    {
        echo "connect {$this->conf['ip']}:{$this->conf['port']} success\n";
        $this->connected = 1;
    }


    public function send($data)
    {
        return $this->cli->send($this->protocol::encode($data));
    }

    public function sendTo($ip, $port, $data)
    {
        $this->cli->sendto($ip, $port, $this->protocol::encode($data));
    }

    public function __receive(\swoole_client $cli, $data)
    {
        if ($this->protocol) {
            $data = $this->protocol::decode($data);
        }
        $this->onReceive($cli, $data);
    }

    abstract public function onReceive(\swoole_client $cli, $data);


    public function onClose(\swoole_client $cli)
    {
        echo 'client onClose:' . PHP_EOL;
    }

    public function onError(\swoole_client $cli)
    {
        echo 'client errCode:' . $cli->errCode . PHP_EOL;
    }

    public function onBufferFull(\swoole_client $cli)
    {

    }

    public function onBufferEmpty(\swoole_client $cli)
    {

    }

}
