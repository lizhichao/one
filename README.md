# One - 一个极简的基于swoole常驻内存框架

> 支持在fpm下运行

## 安装&运行

```shell
composer create-project lizhichao/one-app app
cd app
php App/swoole.php 
```

## 主要功能

- RESTful路由
- 中间件
- WS/TCP……任意协议路由
- ORM模型
- SQL日志模板
- MYSQL连接池
- REDIS连接池
- HTTP/TCP/WEBOSCKET/UDP服务器
- 缓存
- 进程间内存共享
- RPC(http,tcp,udp)
- 日志
- RequestId跟踪

## 运行环境

- `apache/php-fpm`的常规环境 
- 基于`swoole`的阻塞环境
- 基于`swoole`的全协程环境


[详细文档地址](https://www.kancloud.cn/vic-one/php-one/826876)

[使用列子-DEMO](https://github.com/lizhichao/one-demo)

QQ交流群: 731475644

## 路由

```php
Router::get('/', \App\Controllers\IndexController::class . '@index');

// 带参数路由
Router::get('/user/{id}', \App\Controllers\IndexController::class . '@user');

// 路由分组 
Router::group(['namespace'=>'App\\Test\\WebSocket'],function (){
    Router::set('ws','/a','TestController@abc'); // websocket 路由
    Router::set('ws','/b','TestController@bbb'); // websocket 路由
});

```

## orm 模型

### 定义模型
```php
namespace App\Model;

use One\Database\Mysql\Model;

class User extends Model
{
    CONST TABLE = 'users';

    public function articles()
    {
        return $this->hasMany('id',Article::class,'user_id');
    }
}
```

### 使用

在fpm下数据库连接为单列,  
在swoole模式下数据库连接自动切换为连接池

```php
// 查询一条记录
User::find(1);

// 关联查询
User::whereIn('id',[1,2,3])->with('articles')->findAll();

// 更新
user::where('id',1)->update(['name' => 'aaa']);

```

## 日志
```php
Log::debug('abc');
Log::debug(['12,312']);

// 等级|时间|requestId|文件路径:行号|内容
// DEBUG|2018-11-23 15:01:26|WelxrVb5UEoFaDrQ59XnQ|/Controllers/IndexController.php:12|abc
// DEBUG|2018-11-23 15:01:26|WelxrVb5UEoFaDrQ59XnQ|/Controllers/IndexController.php:13|["12,312"]
```

## 缓存
```php
Cache::set('a',1);

// ccc 在 tag1标签下
Cache::set('ccc',3,['tag1']);

// 刷新 tag1 下的所有缓存
Cache::flush('tag1');
```

## session
``` 
$this->session()->get('aaa');
$this->session()->set('aaa',123);

```
        
## 添加一个webSocket服务监听

需要开启什么事件就 重写父类相应事件

```php

[
    'port' => 8082,
    'action' =>  \App\Server\AppWsServer::class, //类名 作为server自带继承 WsServer ；作为监听添加继承 \One\Swoole\Listener\Ws
    'type' => SWOOLE_SOCK_TCP,
    'ip' => '0.0.0.0',
    'set' => [
        'open_http_protocol' => false,
        'open_websocket_protocol' => true
    ]
]

```

## 添加一个TCP服务监听

需要开启什么事件就 重写父类相应事件

```php

[
    'port' => 8083,
    'protocol' => \One\Protocol\Text::class, // 协议
    'action' => \App\Test\MixPro\TcpPort::class, //类名 作为server自带继承 TcpServer ；作为监听添加继承 \One\Swoole\Listener\Tcp
    'type' => SWOOLE_SOCK_TCP,
    'ip' => '0.0.0.0',
    'set' => [
        'open_http_protocol' => false,
        'open_websocket_protocol' => false
    ]
]

```

[详细文档地址](https://www.kancloud.cn/vic-one/php-one/826876)

[使用列子-DEMO](https://github.com/lizhichao/one-demo)

QQ交流群: 731475644
