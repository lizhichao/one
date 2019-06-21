# One - 一个极简高性能php框架，支持[swoole | php-fpm ]环境

<a href="https://github.com/996icu/996.ICU/blob/master/LICENSE"><img src="https://camo.githubusercontent.com/41215df7ff78cefe41536bf897fe1c7e55b10bd2/68747470733a2f2f696d672e736869656c64732e696f2f62616467652f6c6963656e73652d416e74692532303939362d626c75652e737667" alt="LICENSE" data-canonical-src="https://img.shields.io/badge/license-Anti%20996-blue.svg" style="max-width:100%;"></a>

## 适用场景

1. 常规web/app后端服务器
2. 游戏/im即时通讯服务器
3. tcp/udp物联网服务器 
4. 综合性项目，各种混合协议通讯

    
## 背景
在用过`laravel`框架，发现它的`路由`和`数据库ORM`确实非常好用，但是整体确实有点慢，执行到控制器大约需要耗时30ms左右。于是打算做一个拥有非常好用的路由和orm又非常简单的框架。所以你会发现one框的`路由`和`ORM`有laravel的影子。但也有一些自己的特色，例如`ORM`支持自动化缓存(自动化读、写、刷新)保持与数据库同步，对外使用无感知。one框架也支持在fpm下运行，在`fpm`下框架自身整体耗时在1ms左右。


## hello world

安装

```shell
composer create-project lizhichao/one-app app
cd app
php App/swoole.php 
```

测试

```shell
curl http://127.0.0.1:8081/
```

## 主要功能

- RESTful路由
- 中间件
- websocket/tcp/http……任意协议路由
- ORM模型
- 统一的session处理
- mysql连接池
- redis连接池
- 协程 tcp-client 连接池
- HTTP/TCP/WEBOSCKET/UDP服务器
- 缓存
- 进程间内存共享
- RPC(http,tcp,udp)
- 日志
- RequestId跟踪
- 分布式（长连接,事务……）
- Actor (Actor之间 可跨进程，跨机器通讯）

## 路由

```php

Router::get('/', \App\Controllers\IndexController::class . '@index');

// 带参数路由
Router::get('/user/{id}', \App\Controllers\IndexController::class . '@user');

// 路由分组 
Router::group(['namespace'=>'App\\Test\\WebSocket'],function (){
	// websocket 路由
    Router::set('ws','/a','TestController@abc'); 
    Router::set('ws','/b','TestController@bbb'); 
});

// 中间件
Router::group([
    'middle' => [
        \App\Test\MixPro\TestMiddle::class . '@checkSession'
    ]
], function () {
    Router::get('/mix/ws', HttpController::class . '@ws');
    Router::get('/mix/http', HttpController::class . '@http');
    Router::post('/mix/http/loop', HttpController::class . '@httpLoop');
    Router::post('/mix/http/send', HttpController::class . '@httpSend');
});

```

## orm 模型

### 定义模型
```php
namespace App\Model;

use One\Database\Mysql\Model;

// 模型里面不需要指定主键，框架会缓存数据库结构
// 自动匹配主键，自动过滤非表结构里的字段
class User extends Model
{
	// 定义模型对应的表名
    CONST TABLE = 'users';

	// 定义关系
    public function articles()
    {
        return $this->hasMany('id',Article::class,'user_id');
    }
    
    // 定义事件 
    // 是否开启自动化缓存 
    // ……
}
```

### 使用模型

- 在`fpm`下数据库连接为单列,
- 在`swoole`模式下所有数据库操作自动切换为连接池

```php
// 查询一条记录
$user = User::find(1);

// 关联查询
$user_list = User::whereIn('id',[1,2,3])->with('articles')->findAll()->toArray();

// 更新
$r = $user->update(['name' => 'aaa']);
// 或者
$r = user::where('id',1)->update(['name' => 'aaa']);
// $r 为影响记录数量

```

## 缓存
```php
// 设置缓存 无过期时间
Cache::set('ccc',1);

// 设置缓存 1分钟过期
Cache::set('ccc',1,60);

// 获取
Cache::get('ccc');

// 或者 缓存ccc 过期10s 在tag1下面
Cache::get('ccc',function (){
    return '缓存的信息';
},10,['tag1']);

// 刷新tag1下的所有缓存
Cache::flush('tag1');

```
        
## HTTP/TCP/WEBSOCKET/UDP服务器

启动一个websocket服务器，
添加http服务监听，
添加tcp服务监听

```php

[
	 // 主服务器
    'server' => [
        'server_type' => \One\Swoole\OneServer::SWOOLE_WEBSOCKET_SERVER,
        'port' => 8082,
        // 事件回调
        'action' => \One\Swoole\Server\WsServer::class,
        'mode' => SWOOLE_PROCESS,
        'sock_type' => SWOOLE_SOCK_TCP,
        'ip' => '0.0.0.0',
        // swoole 服务器设置参数
        'set' => [
            'worker_num' => 5
        ]
    ],
    // 添加监听
    'add_listener' => [
        [
            'port' => 8081,
            // 事件回调
            'action' => \App\Server\AppHttpPort::class,
            'type' => SWOOLE_SOCK_TCP,
            'ip' => '0.0.0.0',
            // 给监听设置参数
            'set' => [
                'open_http_protocol' => true,
                'open_websocket_protocol' => false
            ]
        ],
        [
            'port' => 8083,
            // 打包 解包协议
            'pack_protocol' => \One\Protocol\Text::class,
            // 事件回调
            'action' => \App\Test\MixPro\TcpPort::class,
            'type' => SWOOLE_SOCK_TCP,
            'ip' => '0.0.0.0',
            // 给监听设置参数
            'set' => [
                'open_http_protocol' => false,
                'open_websocket_protocol' => false
            ]
        ]
    ]
];


```

## RPC

像调用本项目的方法一样调用远程服务器的方法。跨语言，跨机器。

### 服务端
启动rpc服务，框架已经内置了各个协议的rpc服务，添加到到上面配置文件的`action`即可。列如: 支持`http`调用，又支持`tcp`调用。

```php
// http 协议 rpc服务
[
    'port'   => 8082,
    'action' => \App\Server\RpcHttpPort::class,
    'type'   => SWOOLE_SOCK_TCP,
    'ip'     => '0.0.0.0',
    'set'    => [
        'open_http_protocol'      => true,
        'open_websocket_protocol' => false
    ]
],
// tcp 协议 rpc服务
[
    'port'          => 8083,
    'action'        => \App\Server\RpcTcpPort::class,
    'type'          => SWOOLE_SOCK_TCP,
    'pack_protocol' => \One\Protocol\Frame::class, // tcp 打包 解包协议
    'ip'            => '0.0.0.0',
    'set'           => [
        'open_http_protocol'      => false,
        'open_websocket_protocol' => false,
        'open_length_check'       => 1,
        'package_length_func'     => '\One\Protocol\Frame::length',
        'package_body_offset'     => \One\Protocol\Frame::HEAD_LEN,
    ]
]
```
添加具体服务到rpc，
例如有个类`Abc`

```php 

class Abc
{
    private $a;

    // 初始值
    public function __construct($a = 0)
    {
        $this->a = $a;
    }

    // 加法
    public function add($a, $b)
    {
        return $this->a + $a + $b;
    }

    public function time()
    {
        return date('Y-m-d H:i:s');
    }

    // 重新设初始值
    public function setA($a)
    {
        $this->a = $a;
        return $this;
    }
}

```
把`Abc`添加到rpc服务

```php

// 添加Abc到rpc服务
RpcServer::add(Abc::class);

// 如果你不希望把Abc下的所有方法都添加到rpc服务，也可以指定添加。
// 未指定的方法客户端无法调用.
//RpcServer::add(Abc::class,'add');

// 分组添加
//RpcServer::group([
//    // 中间件 在这里可以做 权限验证 数据加解密 等等
//    'middle' => [
//        TestMiddle::class . '@aa'
//    ],
//    // 缓存 如果设置了 当以同样的参数调用时 会返回缓存信息 不会真正调用 单位:秒
//    'cache'  => 10
//], function () {
//    RpcServer::add(Abc::class);
//    RpcServer::add(User::class);
//});
```

### 客户端调用

为了方便调用我们建立一个映射类（one框架可自动生成）

```php
class ClientAbc extends RpcClientHttp {

    // rpc服务器地址
    protected $_rpc_server = 'http://127.0.0.1:8082/';

    // 远程的类 不设置 默认为当前类名
    protected $_remote_class_name = 'Abc';
}
```
调用rpc服务的远程方法， 和调用本项目的方法一样的。你可以想象这个方法就在你的项目里面。

```php
$abc = new ClientAbc(5);

// $res === 10
$res = $abc->add(2,3);

// 链式调用 $res === 105
$res = $abc->setA(100)->add(2,3);

// 如果把上面的模型的User添加到rpc
// RpcServer::add(User::class);
// 下面运行结果和上面一样
// $user_list = User::whereIn('id',[1,2,3])->with('articles')->findAll()->toArray();

```

上面是通过http协议调用的。你也可以通过其他协议调用。例如Tcp协议

```php
class ClientAbc extends RpcClientTcp {

    // rpc服务器地址
    protected $_rpc_server = 'tcp://127.0.0.1:8083/';

    // 远程的类 不设置 默认为当前类名
    protected $_remote_class_name = 'Abc';
}
```

其中类 `RpcClientHttp`,`RpcClientTcp`在框架里。   
你也可以复制到任何其他地方使用。

* [rpc使用例子](https://github.com/lizhichao/one-app/tree/test_rpc)
* [分布式长连接（tcp）例子](https://github.com/lizhichao/one-app/tree/cloud_demo)
* [Actor例子](https://github.com/lizhichao/one-app/tree/actor_demo)
    
## 更多

QQ交流群: 731475644

## TODO

* orm支持[postgresql](https://www.postgresql.org/)
* orm支持[clickhouse](https://clickhouse.yandex/)

