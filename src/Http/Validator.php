<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/3/8
 * Time: 13:35
 */

namespace One\Http;


class Validator
{
    private static $rules = [];

    private $alias = [];

    private $msgs = [];

    private $err = [];

    public function __construct()
    {
        if (count(self::$rules) === 0) {
            $this->setDefaultRules();
        }
    }

    /**
     * @param string $key
     * @param \Closure $call
     * @return $this
     */
    public function addRule(string $key, \Closure $call)
    {
        self::$rules[$key] = $call;
        return $this;
    }

    /**
     * @param array $msgs
     * @return $this
     */
    public function setMessages(array $msgs)
    {
        $this->msgs = $msgs + $this->msgs;
        return $this;
    }

    private function runRule($value, $rules, $name)
    {
        $arr = explode('|', $rules);
        foreach ($arr as $val) {
            $ar = explode(':', $val);
            if (count($ar) > 1) {
                $args = explode(',', $ar[1]);
            } else {
                $args = [];
            }
            if (isset($this->msgs[$ar[0]])) {
                $msg = $this->msgs[$ar[0]];
            } else {
                $msg = self::$rules[$ar[0]]['msg'];
            }
            $msg = str_replace(':attribute', $name, $msg);
            foreach ($args as $i => $g) {
                $msg = str_replace(':arg' . ($i + 1), $g, $msg);
            }
            array_unshift($args, $value);
            if ($this->checkOne(self::$rules[$ar[0]]['fn'], $args, $msg) === false) {
                break;
            }
        }
    }

    /**
     * @param $arr
     * @param array $rules
     * @param array $msgs
     * @return $this
     */
    public function validate(array $arr, array $rules, $msgs = [])
    {
        $this->msgs = $msgs + $this->msgs;
        foreach ($rules as $k => $r) {
            if (isset($arr[$k])) {
                $this->runRule($arr[$k], $r, isset($this->alias[$k]) ? $this->alias[$k] : $k);
            } else if (strpos($r, 'required') === false) {

            } else {
                $this->runRule(null, $r, isset($this->alias[$k]) ? $this->alias[$k] : $k);
            }
        }
        return $this;
    }

    private function checkOne(\Closure $call, array $args, $msg)
    {
        if ($call(...$args) === false) {
            $this->err[] = $msg;
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return array
     */
    public function getErrs()
    {
        return $this->err;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        return count($this->err) === 0 ? true : false;
    }

    /**
     * @param array $arr
     * @return $this
     */
    public function setAliases(array $arr)
    {
        $this->alias = $arr + $this->alias;
        return $this;
    }

    private function setDefaultRules()
    {
        self::$rules = [
            'required'     => [
                'msg' => ':attribute不能为空',
                'fn'  => function ($value) {
                    if ($value === '' || $value === null) {
                        return false;
                    } else {
                        return true;
                    }
                }
            ],
            'numeric'      => [
                'msg' => ':attribute必须是数字',
                'fn'  => function ($value) {
                    return is_numeric($value);
                }
            ],
            'min'          => [
                'msg' => ':attribute不能小于:arg1',
                'fn'  => function ($value, $arg1) {
                    return $value >= $arg1;
                }
            ],
            'max'          => [
                'msg' => ':attribute不能大于:arg1',
                'fn'  => function ($value, $arg1) {
                    return $value <= $arg1;
                }
            ],
            'min_len'      => [
                'msg' => ':attribute不能短于:arg1',
                'fn'  => function ($value, $arg1) {
                    return strlen($value) >= $arg1;
                }
            ],
            'max_len'      => [
                'msg' => ':attribute不能长于:arg1',
                'fn'  => function ($value, $arg1) {
                    return strlen($value) <= $arg1;
                }
            ],
            'unsigned_int' => [
                'msg' => ':attribute必须为大于0的正整数',
                'fn'  => function ($value) {
                    return is_numeric($value) && strpos("{$value}", '.') === false && floatval($value) > 0.1;
                }
            ],
            'email'        => [
                'msg' => ':attribute格式不正确',
                'fn'  => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                }
            ],
            'ip'           => [
                'msg' => ':attribute格式不正确',
                'fn'  => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_IP) !== false;
                }
            ],
            'ip4'          => [
                'msg' => ':attribute格式不正确',
                'fn'  => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
                }
            ],
            'ip6'          => [
                'msg' => ':attribute格式不正确',
                'fn'  => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
                }
            ]
        ];

    }
}