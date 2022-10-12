<?php

namespace One\I18n;

class Lang
{
    /**
     * @var string
     */
    private static $path;

    public static function setPath($path): void
    {
        self::$path = rtrim($path, '/');
    }

    public function getTranslate(string $key, array $parameters=[]): mixed
    {
        $term = $this->detectTranslate(key: $key, lang: $this->detectLanguage());
        return is_string($term)
            ? $this->replaceAttr(term: $term, params: $parameters)
            : $term;
    }

    private function replaceAttr(string $term, array $params): string
    {
        array_walk($params, function($val, $key) use (&$term) {
            $term = str_replace(":$key", $val, $term);
        });

        return $term;
    }

    private function detectLanguage(): string
    {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);
        return strtolower($lang);
    }

    private function detectTranslate(string $key, string $lang): mixed
    {
        static $config = null;
        $res = array_get($config, $key);
        if (!$res) {
            $p = strpos($key, '.');
            if ($p !== false) {
                $name          = substr($key, 0, $p);
                $config[$name] = require_once self::$path . "/{$lang}/{$name}.php";
            } else {
                $config[$key] = require_once self::$path . "/{$lang}/{$key}.php";
            }
            $res = array_get($config, $key);
        }
        return $res;
    }
}