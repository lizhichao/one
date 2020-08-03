<?php

namespace One\Database\ClickHouse;

use One\Facades\Cache;

trait StructTrait
{
    private static $struct = [];

    protected function getStruct()
    {
        $dns = $this->connect->getKey();
        if (!isset(self::$struct[$dns][$this->from])) {
            $key                             = md5(__FILE__ . $dns . $this->from);
            $str                             = unserialize(Cache::get($key, function () {
                $pdo    = $this->getConnect();
                $arr    = $pdo->select('desc `' . $this->from . '`');
                $fields = [];
                foreach ($arr as $v) {
                    if (stripos($v['type'], 'Nullable') !== false) {
                        $fields[$v['name']] = [
                            'is_null' => 0,
                            'type'    => $v['type']

                        ];
                    } else {
                        $fields[$v['name']] = [
                            'is_null' => 1,
                            'type'    => $v['type']
                        ];
                    }
                }
                $this->push($pdo);
                return serialize(['field' => $fields]);
            }, 60 * 60 * 24));
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
     * 过滤
     * @param $data
     */
    public function filter($data)
    {
        $field = $this->getStruct()['field'];
        foreach ($data as $k => $v) {
            if (!isset($field[$k])) {
                unset($data[$k]);
                continue;
            }
//            $data[$k] = $this->toSafeVal($v, $field[$k]['type']);
        }
        return $data;
    }

//    /**
//     * @param string|array $v
//     * @param string $type
//     * @return array|float|int|string
//     */
//    public function toSafeVal($v, $type)
//    {
//        $type = strtolower($type);
//        $ints = [
//            'uint8'  => 1,
//            'uint16' => 1,
//            'uint32' => 1,
//            'uint64' => 1,
//            'int8'   => 1,
//            'int16'  => 1,
//            'int32'  => 1,
//            'int64'  => 1,
//        ];
//        if (isset($ints[$type])) {
//            return intval($v);
//        }
//
//        if ($type === 'float32' || $type === 'float64' || stripos($type, 'decimal') === 0) {
//            return floatval($v);
//        }
//
//        if (is_array($v)) {
//            if (stripos($type, 'array') === 0 || stripos($type, 'tuple') === 0) {
//                $p = substr($type, 6, -1);
//                foreach ($v as $i => $n_v) {
//                    $v[$i] = $this->toSafeVal($n_v, $p);
//                }
//                return $v;
//            }
//        }
//        return addslashes($v);
//    }
}