<?php

namespace One\Exceptions;

use One\ConfigTrait;

class Handler
{
    use ConfigTrait;

    public static function render(HttpException $e)
    {
        if (isset(self::$conf['render'])) {
            return self::$conf['render']($e);
        }

        $code = $e->getCode();
        if ($code === 0) {
            $code = 1;
        }
        $e->response->code($code);

        if ($e->response->getHttpRequest()->isJson()) {
            return $e->response->json($e->getMessage(), $code);
        } else {
            $file = _APP_PATH_VIEW_ . '/exceptions/' . $code . '.php';
            if (file_exists($file)) {
                return $e->response->tpl('exceptions/' . $code, ['e' => $e]);
            } else {
                return $e->response->json($e->getMessage(), $code);
            }
        }
    }
}

