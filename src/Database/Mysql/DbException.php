<?php

namespace One\Database\Mysql;

use One\Facades\Log;

class DbException extends \Exception
{
    public function __construct($message = "", $code = 1, $previous = null)
    {
        Log::error($message, 3 + $code);
        parent::__construct($message, $code, $previous);
    }
}