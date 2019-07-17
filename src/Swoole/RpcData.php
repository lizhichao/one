<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/7/17
 * Time: 17:35
 */

namespace One\Swoole;


class RpcData
{
    public $data;

    public $obj;

    public function __construct($obj, $d)
    {
        $this->obj  = $obj;
        $this->data = $d;
    }
}