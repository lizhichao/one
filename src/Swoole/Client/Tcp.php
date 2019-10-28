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

    private $retry_count = 3;

    private $max_retry_count = 1;

    private $transaction_id = null;

    /**
     * @var ProtocolAbstract
     */
    private $protocol = null;

    public function __construct($key = 'default')
    {
        if ($key) {
            $this->setConnection($key);
        }
    }

    public function setConnection($key)
    {
        if (!isset(self::$conf[$key])) {
            echo "warn:client {$key} no find\n";
            return $this;
        }
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
            if (isset($this->config['create_call'])) {
                $this->config['create_call']->call($this, 1);
            }
            return $client;
        } else {
            if (isset($this->config['create_call'])) {
                $is_retry = $this->config['create_call']->call($this, 0);
                if ($is_retry === true) {
                    return $this->createRes();
                }
            }
            throw new \Exception('连接失败 tcp://' . $this->config['ip'] . ':' . $this->config['port'], 650);
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
                    if (isset($this->config['close_call'])) {
                        $this->config['close_call']->call($this, $cli);
                    }
                    $cli = $this->pop();
                    $r   = @$cli->send($data);
                    $retry++;
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
        $this->retry_count = 3;
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
        $this->retry_count = 3;
        return $ret;
    }

}