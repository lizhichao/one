<?php

namespace One;

class Log
{
    use ConfigTrait;

    private $levels = [
        'ERROR', 'WARN', 'NOTICE', 'DEBUG'
    ];

    public function __construct()
    {

    }

    /**
     * @param $data
     * @param int $k
     * @param $prefix
     */
    public function debug($data, $k = 0, $prefix = 'debug')
    {
        $this->_log($data, $k + 2, 3, $prefix);
    }

    /**
     * @param $data
     * @param int $k
     * @param $prefix
     */
    public function notice($data, $k = 0, $prefix = 'notice')
    {
        $this->_log($data, $k + 2, 2, $prefix);
    }

    /**
     * @param $data
     * @param int $k
     * @param $prefix
     */
    public function warn($data, $k = 0, $prefix = 'warn')
    {
        $this->_log($data, $k + 2, 1, $prefix);
    }

    /**
     * @param $data
     * @param int $k
     * @param $prefix
     */
    public function error($data, $k = 0, $prefix = 'error')
    {
        $this->_log($data, $k + 2, 0, $prefix);
    }

    private function utf8ize($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = $this->utf8ize($value);
            }
        } else if (is_string($mixed)) {
            return iconv('UTF-8', 'UTF-8//IGNORE', $mixed);
        }
        return $mixed;
    }


    private function _log($mixed, $k = 0, $code = 3, $prefix = 'vic')
    {
        if (!is_dir(self::$conf['path'])) {
            mkdir(self::$conf['path'], 0755, true);
        }
        $path = self::$conf['path'] . '/' . $prefix . '-' . date('Y-m-d') . '.log';
        if (is_string($mixed)) {
            $data = str_replace("\n", ' ', $mixed);
        } else {
            $data = json_encode($mixed, JSON_UNESCAPED_UNICODE);
            if ($data === false && json_last_error() === JSON_ERROR_UTF8) {
                $mixed = $this->utf8ize($mixed);
                $data  = json_encode($mixed, JSON_UNESCAPED_UNICODE);
            }
        }
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 16);
        $info  = isset($trace[$k]) ? $trace[$k] : end($trace);
        $name  = str_replace(_APP_PATH_, '', $info['file']);
        $line  = $info['line'];

        $code = $this->levels[$code];

        $trace_id = $this->getTraceId();

        if (isset(self::$conf['save_drive'])) {
            self::$conf['save_drive']($code, $trace_id, $name, $line, $data);
        } else {
            $str = $code . '|' . date('Y-m-d H:i:s') . '|' . $trace_id . '|' . $name . ':' . $line . '|' . $data . "\n";
            error_log($str, 3, $path);
        }

    }

    private $_traceId = [];

    public function setTraceId($id)
    {
        $cid                  = get_co_id();
        $this->_traceId[$cid] = $id;
        return $cid;
    }

    /**
     * 在协程环境统一TraceId
     * @param $id
     * @return string
     */
    public function bindTraceId($id, $is_pid = false)
    {
        if (_CLI_) {
            if ($is_pid) {
                $pid = $id;
                $id  = get_co_id();
            } else {
                $pid = get_co_id();
            }
            if ($pid == -1 && _DEBUG_) {
                echo 'warn bindTraceId false : ' . $id . "\n";
            }
            if (!isset($this->_traceId[$pid]) && _DEBUG_) {
                echo 'warn bindTraceId get pid false : ' . $pid . "\n";
            }
            $this->_traceId[$id] = $this->_traceId[$pid];
        }
        return $id;
    }

    /**
     * 请求完成刷新 清除已经关闭的id
     */
    public function flushTraceId($id)
    {
        if (_CLI_) {
            if (isset($this->_traceId[$id])) {
                unset($this->_traceId[$id]);
            }
        }
    }


    public function getTraceId()
    {
        $trace_id = self::$conf['id'];
        $cid      = get_co_id();
        if ($cid > -1) {
            if (isset($this->_traceId[$cid])) {
                $trace_id = $this->_traceId[$cid];
            } else if (_DEBUG_) {
                //如果直接调用go创建协程这里获取不到id 所有创建协程请调用oneGo
                echo 'warn get trace_id fail : ' . $cid . "\n";
            }
        }
        return $trace_id;
    }
}