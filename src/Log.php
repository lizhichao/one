<?php

namespace One;

class Log
{
    use ConfigTrait;

    private $levels = [
        'ERROR', 'WARN', 'NOTICE', 'DEBUG'
    ];

    const LogId = 'log_id';


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

        if (isset(self::$conf['fn_save'])) {
            self::$conf['fn_save']($code, $trace_id, $name, $line, $data, $prefix);
        } else {
            $str = $code . '|' . date('Y-m-d H:i:s') . '|' . $trace_id . '|' . $name . ':' . $line . '|' . $data . "\n";
            error_log($str, 3, $path);
        }

    }


    public function setTraceId($id)
    {
        if (_CLI_) {
            \One\Swoole\Context::set(self::LogId, $id);
        } else {
            Context::set(self::LogId, $id);
        }
    }

    public function getTraceId()
    {
        if (_CLI_) {
            $id = \One\Swoole\Context::get(self::LogId);
        } else {
            $id = Context::get(self::LogId);
        }
        if ($id === null) {
            $id = uuid();
            $this->setTraceId($id);
        }
        return $id;
    }
}