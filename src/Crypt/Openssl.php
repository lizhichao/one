<?php

namespace One\Crypt;

use One\ConfigTrait;

class Openssl
{
    use ConfigTrait;

    /**
     * 密码加密
     * @param $str
     * @return bool|string
     */
    public function hash($str)
    {
        return password_hash($str, PASSWORD_DEFAULT);
    }

    /**
     * 验证
     * @param $str
     * @param $hash
     * @return bool
     */
    public function verifyHash($str, $hash)
    {
        return password_verify($str, $hash);
    }

    /**
     * 解密
     * @param $secret
     * @return string
     */
    public function decode($secret)
    {
        return openssl_decrypt($secret, self::$conf['method'], self::$conf['secret_key'], false);
    }

    /**
     * 加密
     * @param $data
     * @return string
     */
    public function encode($data)
    {
        return openssl_encrypt($data, self::$conf['method'], self::$conf['secret_key'], false);
    }

    /**
     * 签名
     * @param $data
     * @return string
     */
    public function sign($data)
    {
        return sha1($data . self::$conf['sign_key']);
    }

    /**
     * 检测签名
     * @param $data
     * @param $sign
     * @return bool
     */
    public function checkSign($data, $sign)
    {
        return $this->sign($data) == $sign;
    }
}