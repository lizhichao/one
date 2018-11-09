<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/12
 * Time: 16:32
 * Tcp协议带路由
 * |----|-|-|-|...|...|
 * length|url_length|uuid_length|session_id_length|url|uuid|session_id|主体内容
 */

namespace One\Protocol;


class TcpRouter extends ProtocolAbstract
{

    const HEAD_LEN = 4;
    const HEAD_ROUTER_LEN = 1;
    const HEAD_SID_LEN = 1;
    const HEAD_UUID_LEN = 1;


    public static function length($data)
    {
        if (strlen($data) < 4) {
            return 0;
        }
        $unpack_data = unpack('Ntotal_length', $data);
        if ($unpack_data['total_length'] <= 0) {
            return -1;
        } else {
            return $unpack_data['total_length'];
        }
    }

    public static function encode($buf)
    {
        $len = self::HEAD_LEN + strlen($buf);
        return pack('N', $len) . $buf;
    }

    public static function decode($buf)
    {
        $router_len = base_convert(bin2hex(substr($buf, 4, 1)), 16, 10);
        $uuid_len = base_convert(bin2hex(substr($buf, 5, 1)), 16, 10);
        $sid_len = base_convert(bin2hex(substr($buf, 6, 1)), 16, 10);
        $obj = new TcpRouterData();
        $obj->url = substr($buf, 7, $router_len);
        $obj->uuid = substr($buf, 7 + $router_len, $uuid_len);
        $obj->session_id = substr($buf, 7 + $router_len + $uuid_len, $sid_len);
        $obj->body = substr($buf, 7 + $router_len + $uuid_len + $sid_len);
        return $obj;
    }

}
