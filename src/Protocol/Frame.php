<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2018/10/12
 * Time: 16:32
 */

namespace One\Protocol;


class Frame extends ProtocolAbstract
{

    const HEAD_LEN = 4;


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
        return substr($buf,self::HEAD_LEN);
    }

}
