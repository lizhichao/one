<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/11/14
 * Time: 15:32
 */

namespace One\Swoole\Event;

trait TaskEvent
{
    public function onTask(\Swoole\Server $server, $task_id, $src_worker_id, $data)
    {

    }

    public function onFinish(\Swoole\Server $server, $task_id, $data)
    {

    }

}