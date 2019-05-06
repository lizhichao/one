<?php

namespace One\Database\Mysql;

use One\Facades\Cache;

trait StructTrait
{
    private static $struct = [];

    protected function getStruct()
    {
        $dns = $this->connect->getKey();
        if (!isset(self::$struct[$dns][$this->from])) {
            $key                             = md5(__FILE__ . $dns . $this->from);
            $str                             = Cache::get($key, function () {
                $pdo    = $this->getConnect();
                $arr    = $pdo->query('desc ' . $this->from)->fetchAll(\PDO::FETCH_ASSOC);
                $fields = [];
                $pri    = '';
                foreach ($arr as $v) {
                    if ($v['Key'] == 'PRI') {
                        $pri = $v['Field'];
                    } else if ($v['Null'] == 'YES') {
                        $fields[$v['Field']] = 0;
                    } else {
                        $fields[$v['Field']] = 1;
                    }
                }
                $this->push($pdo);
                return ['field' => $fields, 'pri' => $pri];
            }, 60 * 60 * 24);
            self::$struct[$dns][$this->from] = $str;
        }
        return self::$struct[$dns][$this->from];
    }


    public function flushTableInfo()
    {
        $dns = $this->connect->getKey();
        $key = md5(__FILE__ . $dns . $this->from);
        Cache::del($key);
    }

    /**
     * 获取主键
     */
    protected function getPriKey()
    {
        if ($this->pri_key !== '') {
            return $this->pri_key;
        }
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
        return array_merge([$this->getPriKey()], $this->getStruct()['field']);
    }
}