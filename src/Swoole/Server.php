<?php
/**
 * Created by PhpStorm.
 * User: tanszhe
 * Date: 2018/8/24
 * Time: 上午11:08
 */

namespace One\Swoole;

use One\Facades\Log;
use One\Protocol\ProtocolAbstract;
use Swoole\Process;

/**
 * Class Server
 * @package One\Swoole
 * @mixin OneServer
 */
class Server
{

    protected $conf = [];

    /**
     * @var ProtocolAbstract
     */
    protected $protocol = null;

    /**
     * @var \Swoole\WebSocket\Server
     */
    protected $server = null;

    public $worker_id = 0;
    public $is_task = false;
    public $pid = 0;


    public function __construct(\Swoole\Server $server, array $conf)
    {
        $this->server = $server;
        $this->conf   = $conf;
        if (isset($conf['pack_protocol'])) {
            $this->protocol = $conf['pack_protocol'];
        }
    }

    public function send($fd, $data, $from_id = 0, $use_protocol = true)
    {
        if ($this->protocol && $use_protocol) {
            $data = $this->protocol::encode($data);
        }
        $this->server->send($fd, $data, $from_id);
    }


    public function onStart(\Swoole\Server $server)
    {

    }

    public function onShutdown(\Swoole\Server $server)
    {

    }

    public function onWorkerStart(\Swoole\Server $server, $worker_id)
    {
        $this->worker_id = $worker_id;
        $this->is_task   = $server->taskworker ? true : false;
        $this->pid       = $server->worker_pid;

        @swoole_set_process_name(($server->taskworker ? 'one_task' : 'one_worker') . '_' . $this->server->p_name . '_' . $worker_id);
        Process::signal(SIGPIPE, function ($signo) {
            echo "socket close\n";
        });
    }

    public function onWorkerStop(\Swoole\Server $server, $worker_id)
    {

    }

    public function onWorkerExit(\Swoole\Server $server, $worker_id)
    {

    }

    public function onWorkerError(\Swoole\Server $server, $worker_id, $worker_pid, $exit_code, $signal)
    {

    }

    public function onClose(\Swoole\Server $server, $fd, $reactor_id)
    {

    }

    public function onPipeMessage(\Swoole\Server $server, $src_worker_id, $message)
    {

    }

    public function onManagerStart(\Swoole\Server $server)
    {
        @swoole_set_process_name('one_manager_'.$this->server->p_name);
    }

    public function onManagerStop(\Swoole\Server $server)
    {

    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->server, $name)) {
            return $this->server->$name(...$arguments);
        } else {
            throw new \Exception('方法不存在:' . $name, 10);
        }

    }
}