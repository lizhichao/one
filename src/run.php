<?php
if (!defined('_APP_PATH_')) {
    exit('Please define the project path with _APP_PATH_');
}
define('_START_TIME_', microtime(true));

define('_CLI_', php_sapi_name() === 'cli' && class_exists('\Swoole\Coroutine'));

if (!defined('_DEBUG_')) {
    define('_DEBUG_', false);
}
require __DIR__ . '/helper.php';

require _APP_PATH_ . '/config.php';

\One\Http\Router::loadRouter();
