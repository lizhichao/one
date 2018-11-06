<?php

namespace One\Database\Mysql;

use One\Facades\Cache;

trait StructTrait
{
    private static $struct = [];

    protected function getStruct()
    {
        if (!isset(self::$struct[$this->from])) {
            $key = md5(__FILE__ . $this->connect->getDns() . $this->from);
            $str = Cache::get($key, function () {
                $arr = $this->connect->getPdo()->query('desc ' . $this->from)->fetchAll(\PDO::FETCH_ASSOC);
                $fields = [];
                $pri = '';
                foreach ($arr as $v) {
                    if ($v['Key'] == 'PRI') {
                        $pri = $v['Field'];
                    } else if ($v['Null'] == 'YES') {
                        $fields[$v['Field']] = 0;
                    } else {
                        $fields[$v['Field']] = 1;
                    }
                }
                return ['field' => $fields, 'pri' => $pri];
            }, 60 * 60 * 24);
            self::$struct[$this->from] = $str;
        }
        return self::$struct[$this->from];
    }

    /**
     * 获取主键
     */
    protected function getPriKey()
    {
        return $this->getStruct()['pri'];
    }

    /**
     * 过滤
     * @param $data
     */
    public function filter($data)
    {
        $field = $this->getStruct()['field'];
        foreach ($data as $k => $v) {
            if (!isset($field[$k])) {
                unset($data[$k]);
            }
        }
        return $data;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return array_merge([$this->getPriKey()],$this->getStruct()['field']);
    }
}