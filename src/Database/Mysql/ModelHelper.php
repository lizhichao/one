<?php


namespace One\Database\Mysql;


class ModelHelper
{
    protected $namespace   = 'App\\Model';
    protected $extend      = Model::class;
    protected $extend_name = 'Model';
    protected $dir         = 'App/Model/';

    public function __construct($namespace = '', $extend = '')
    {
        if ($namespace) {
            $this->namespace = $namespace;
        }
        if ($extend) {
            $this->extend = trim($extend, '\\');
            $i            = strrpos($this->extend, '\\');
            if ($i !== false) {
                $this->extend_name = substr($this->extend, $i + 1);
            } else {
                $this->extend_name = $this->extend;
                $this->extend      = '';
            }
        }
        $this->dir = str_replace('\\', '/', $this->namespace) . '/';
    }

    protected $fields = [];

    protected $table = '';

    public function set($table)
    {
        $res = Model::cache(0)->query('show full fields from ' . $table)->toArray();
        $r   = [];
        foreach ($res as $val) {
            $r[] = [
                strpos($val['Type'], 'int') !== false ? 'int' : 'string',
                $val['Field'],
                $val['Comment']
            ];
        }
        $this->fields = $r;
        $this->table  = $table;
        return $this;
    }

    /**
     * @return array
     */
    public function getTables()
    {
        $res = Model::cache(0)->query('show tables')->toArray();
        $k = key($res[0]);
        return array_values(array_column($res,$k));
    }

    /**
     * 创建模型
     * @param null $dir
     * @return false|int
     */
    public function createModel($dir = null)
    {
        if ($dir === null) {
            $dir = $this->dir;
        }

        $dir = _APP_PATH_ . '/../' . $dir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($dir . $this->getModelName($this->table) . '.php', $this->info());
    }

    /**
     * 获取模型信息
     * @return string
     */
    public function info()
    {
        $table  = $this->table;
        $fields = $this->fields;
        $model  = $this->getModelName($table);
        $str    = [
            '<?php',
            '',
            'namespace ' . $this->namespace . ';',
            '',
        ];
        if ($this->extend) {
            $str[] = 'use ' . $this->extend . ';';
            $str[] = '';
        }
        $str[] = '/**';
        $str[] = ' * Class ' . $model;
        foreach ($fields as $field) {
            $str[] = " * @property {$field[0]} \${$field[1]} {$field[2]}";
        }
        $str[] = ' */';
        $str[] = 'class ' . $model . ' extends ' . $this->extend_name;
        $str[] = '{';
        $str[] = '    CONST TABLE = \'' . $table . '\';';
        $str[] = '}';
        return implode(PHP_EOL, $str);
    }

    protected function getModelName($table_name)
    {
        return implode('', array_map('ucfirst', explode('_', $table_name)));
    }

}