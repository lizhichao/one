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
     * @param array $call
     * @return $this
     */
    public function addRule(string $key, array $call)
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

    private $_rule_keys = [
        'stop' => 1
    ];

    private function runRule($value, $rules, $name)
    {
        $arr = explode('|', $rules);
        foreach ($arr as $val) {
            if (isset($this->_rule_keys[$val])) {
                continue;
            }
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
            if (!isset(self::$rules[$ar[0]])) {
                throw new \Exception('未定义的验证规则:' . $ar[0], 5001);
            }
            if ($this->checkOne(self::$rules[$ar[0]]['fn'], $args, $msg) === false) {
                return false;
            }
        }
        return true;
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
                $ret = $this->runRule($arr[$k], $r, isset($this->alias[$k]) ? $this->alias[$k] : $k);
            } else if (strpos($r, 'required') === false) {
                $ret = true;
            } else {
                $ret = $this->runRule(null, $r, isset($this->alias[$k]) ? $this->alias[$k] : $k);
            }
            if ($ret === false && strpos($r, 'stop') !== false) {
                break;
            }
        }
        return $this;
    }

    private function checkOne(\Closure $call, array $args, $msg)
    {
        if ($call->call($this, ...$args) === false) {
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
            'required' => [
                'msg' => ':attribute不能为空',
                'fn'  => function ($value) {
                    if ($value === '' || $value === null) {
                        return false;
                    } else {
                        return true;
                    }
                }
            ],
            'numeric'  => [
                'msg' => ':attribute必须是数字',
                'fn'  => function ($value) {
                    return is_numeric($value);
                }
            ],
            'int'      => [
                'msg' => ':attribute必须是整数',
                'fn'  => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_INT) !== false;
                }
            ],
            'min'      => [
                'msg' => ':attribute不能小于:arg1',
                'fn'  => function ($value, $arg1) {
                    return $value >= $arg1;
                }
            ],
            'max'      => [
                'msg' => ':attribute不能大于:arg1',
                'fn'  => function ($value, $arg1) {
                    return $value <= $arg1;
                }
            ],
            'min_len'  => [
                'msg' => ':attribute不能短于:arg1',
                'fn'  => function ($value, $arg1) {
                    return strlen($value) >= $arg1;
                }
            ],
            'max_len'  => [
                'msg' => ':attribute不能长于:arg1',
                'fn'  => function ($value, $arg1) {
                    return strlen($value) <= $arg1;
                }
            ],
            'uint'     => [
                'msg' => ':attribute必须为大于0的正整数',
                'fn'  => function ($value) {
                    $v = filter_var($value, FILTER_VALIDATE_INT);
                    return $v && $v > 0;
                }
            ],
            'email'    => [
                'msg' => ':attribute格式不正确',
                'fn'  => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                }
            ],
            'ip'       => [
                'msg' => ':attribute格式不正确',
                'fn'  => function ($value) {
                    return filter_var($value, FILTER_VALIDATE_IP) !== false;
                }
            ]
        ];

    }
}