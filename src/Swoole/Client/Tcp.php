<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/22
 * Time: 15:48
 */

namespace One\Swoole\Client;

use One\ConfigTrait;
use One\Protocol\ProtocolAbstract;
use One\Swoole\Pools;
use Swoole\Coroutine\Client;

class Tcp
{
    use ConfigTrait, Pools;

    private $key = '';

    private $config = [];

    private $max_retry_count = 1;

    /**
     * @var ProtocolAbstract
     */
    private $protocol = null;

    public function __construct($key = 'default')
    {
        $this->setConnection($key);
    }

    public function setConnection($key)
    {
        $this->key    = $key;
        $this->config = self::$conf[$key];
        if (isset($this->config['pack_protocol'])) {
            $this->protocol = $this->config['pack_protocol'];
        }
        $this->max_retry_count = $this->config['max_connect_count'] + 3;
        return $this;
    }

    private function createRes()
    {
        $client = new \Swoole\Coroutine\Client(SWOOLE_TCP);
        if (isset($this->config['set'])) {
            $client->set($this->config['set']);
        }
        $r = $client->connect($this->config['ip'], $this->config['port'], $this->config['time_out']);
        if ($r) {
            return $client;
        } else {
            throw new \Exception('连接失败 tcp://' . $this->config['ip'] . ':' . $this->config['port']);
        }
    }

    public function call($data, $time_out = 3.0)
    {
        $cli = $this->pop();
        if ($this->protocol !== null) {
            $data = $this->protocol::encode($data);
        }
        $r     = @$cli->send($data);
        $retry = 0;
        if ($r === false) {
            retry:{
                do {
                    $cli->close();
                    self::$connect_count--;
                    $cli = $this->pop();
                    $r   = @$cli->send($data);
                    $retry++;
                    echo "retry\n";
                } while ($retry < $this->max_retry_count && $r === false);
            }
        }
        $ret = $cli->recv($time_out);
        if ($ret === '' && $retry === 0) {
            goto retry;
        }
        if ($this->protocol !== null) {
            $ret = $this->protocol::decode($ret);
        }
        $this->push($cli);
        return $ret;

    }


    public function recv($time_out = 3.0)
    {
        $rs  = $this->pop();
        $ret = $rs->recv($time_out);
        if ($this->protocol !== null) {
            $ret = $this->protocol::decode($ret);
        }
        $this->push($rs);
        return $ret;
    }

    public function send($data)
    {
        $rs = $this->pop();
        if ($this->protocol !== null) {
            $data = $this->protocol::encode($data);
        }
        $ret = $rs->send($data);
        $this->push($rs);
        return $ret;
    }

}