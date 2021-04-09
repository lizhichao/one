## One - 一个极简高性能php框架，支持[swoole | php-fpm ]环境

- 快   - 即使在`php-fpm`下也能`1ms`内响应请求
- 简单 - 让你重点关心用`one`做什么，而不是怎么用`one`
- 灵活 - 各个组件松耦合，可以灵活搭配使用，使用方法保持一致
    - 原生sql可以和模型关系`with`搭配使用，关系可以跨数据库类型
    - session 可以在http,websocket甚至tcp,udp和cli下使用
    - ...
- 高效 - 运行性能，开发效率，易维护。
- 轻量 - 无其他依赖，从路由、orm所有组件代码量一共不超过500k，若二次开发没有复杂的调用关系，可快速掌握设计原理
    
QQ交流群：731475644

珍爱生命，抵制996
<a href="https://github.com/996icu/996.ICU/blob/master/LICENSE"><img src="https://camo.githubusercontent.com/41215df7ff78cefe41536bf897fe1c7e55b10bd2/68747470733a2f2f696d672e736869656c64732e696f2f62616467652f6c6963656e73652d416e74692532303939362d626c75652e737667" alt="LICENSE" data-canonical-src="https://img.shields.io/badge/license-Anti%20996-blue.svg" style="max-width:100%;"></a>


## hello world

安装

```shell
composer create-project lizhichao/one-app app
cd app
php App/swoole.php 

# 停止 ： `php App/swoole.php -o stop`  
# 重启 ： `php App/swoole.php -o reload`  
# 守护进程启动 ： `php App/swoole.php -o start`  

curl http://127.0.0.1:8081/
```

## 性能

**参考：**

* [性能测试1 (mysql + orm)](https://www.techempower.com/benchmarks/#section=test&runid=57b25c85-082a-4013-b572-b0939006eaff&hw=ph&test=query&d=dz&a=2&o=e)
* [性能测试2](https://github.com/the-benchmarker/web-frameworks)


## 主要组件

- 路由
    - 支持贪婪匹配和优先级
    - 支持ws/tcp/http……任意协议
    - 性能好，添加几万条路由也不会降低解析性能
    - 路由分组，中间件……该有的都有
- ORM模型
    - 支持数据库：mysql,clickHouse，
    - 关系处理：一对一，一对多，多对一，多态…… 各种关系的都有，可以跨数据库类型关联
    - 缓存：自动刷新数据 支持配置各种缓存粒度
    - 事件：所有操作都能捕获 包括你用原生sql操作数据库
    - 数据库连接：同步、异步、阻塞、断线重连都支持
    - sql模板： 自动生成模板id，可了解项目有哪些类型sql，以及调用次数占比情况，对后期数据优化提供数据支持。
    - statement复用：提供sql执行性能
- rpc
    - 可自动生成远程方法映射，支持ide提示
    - 直接调用映射方法 == 调用远程方法，支持链式调用
    - 支持`rpc中间件`，鉴权、加解密、缓存……
- 日志
    - 信息完整：记录完整的文件名+行号可快速定位代码位置
    - requestId：可轻松查看整个请求日志信息和服务关系

- ...
    - QQ交流群: 731475644


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
    Router::get('/user/{id}', \App\Controllers\IndexController::class . '@user');
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

    
## 更多

* [各种协议通讯列子](https://github.com/lizhichao/one-demo)
* [rpc使用例子](https://github.com/lizhichao/one-app/tree/test_rpc)
* [分布式长连接（tcp）例子](https://github.com/lizhichao/one-app/tree/cloud_demo)
* [Actor例子](https://github.com/lizhichao/one-app/tree/actor_demo)

## 文档

* [文档地址](https://github.com/lizhichao/one-doc/blob/master/SUMMARY.md)
* [参数验证器](https://segmentfault.com/a/1190000018434298)


## TODO

* 支持[Workerman](https://github.com/walkor/Workerman)
* orm支持[postgresql](https://www.postgresql.org/)

QQ交流群: 731475644

## 我的其他开源项目
* [纯php的分词](https://github.com/lizhichao/VicWord)
* [clickhouse tcp 客户端](https://github.com/lizhichao/one-ck)

