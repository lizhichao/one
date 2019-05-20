<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/9
 * Time: 11:05
 */

namespace One\Swoole;

use One\Facades\Log;
use One\Protocol\TcpRouterData;

class TcpController
{
    /**
     * @var TcpRouterData
     */
    protected $data;

    /**
     * @var Server
     */
    protected $server;

    /**
     * @var Session
     */
    protected $session = null;

    protected $go_id = -1;


    public function __construct($data, $server)
    {
        $this->go_id  = get_co_id();
        $this->data   = $data;
        $this->server = $server;
        if ($this->data->session_id) {
            $this->session = new Session(null, $this->data->session_id);
        }
    }

    public function __destruct()
    {

    }

}