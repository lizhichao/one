[English](https://github.com/lizhichao/one/blob/master/README.md) | [中文](https://github.com/lizhichao/one/blob/master/README-CN.md)

## One - A minimalist high-performance php framework that supports the [swoole | php-fpm] environment

- Fast - can respond to requests within `1ms` even under `php-fpm`
- Simple - let you focus on what you do with `one` instead of how to use `one`
- Flexible - each component is loosely coupled, can be flexibly matched and used, and the method of use is consistent
    - Native sql can be used with model relation `with`, relation can cross database types
    - session can be used under http, websocket or even tcp, udp and cli
    - ...
- High efficiency - operational performance, development efficiency, and easy maintenance.
- Lightweight - no other dependencies, the total code amount of all components from routing and orm does not exceed 500k. If there is no complicated calling relationship in secondary development, you can quickly grasp the design principle
- Distributed ORM - orm supports wireless sub-databases and sub-tables. The usage method remains the same
    

## hello world

install

```shell
composer create-project lizhichao/one-app app
cd app
php App/swoole.php 

# stop ： `php App/swoole.php -o stop`  
# reload ： `php App/swoole.php -o reload`  
# Daemon start ： `php App/swoole.php -o start`  

curl http://127.0.0.1:8081/
```

## performance

**reference：**

* [test1 (mysql + orm)](https://www.techempower.com/benchmarks/#section=test&runid=57b25c85-082a-4013-b572-b0939006eaff&hw=ph&test=query&d=dz&a=2&o=e)
* [test2](https://github.com/the-benchmarker/web-frameworks)


## Main components

- Router
    - Support greedy matching and priority
    - Support ws/tcp/http……any protocol
    - Good performance, adding tens of thousands of routes will not reduce the parsing performance
    - Routing grouping, middleware...all there should be
- ORM
    - Support database: mysql, clickHouse,
    - Relation processing: one-to-one, one-to-many, many-to-one, polymorphism... There are various relations, which can be related across database types
    - Cache: Automatically refresh data, support configuration of various cache granularities
    - Event: All operations can be captured, including you use native SQL to operate the database
    - Database connection: synchronous, asynchronous, blocking, disconnection and reconnection are all supported
    - sql template: automatically generate template id, you can understand what types of sql the project has, and the proportion of the number of calls, and provide data support for later data optimization.
    - Statement reuse: Provide SQL execution performance
    - The model supports dynamic sub-database sub-table and massive data
- rpc
    - Can automatically generate remote method mapping, support ide prompt
    - Direct call mapping method == call remote method, support chain call
    - Support `rpc middleware`, authentication, encryption and decryption, caching...
- Log
    - Complete information: record the complete file name + line number to quickly locate the code location
    - requestId: You can easily view the entire request log information and service relationship

## Router

```php

Router::get('/', \App\Controllers\IndexController::class . '@index');

// router with params
Router::get('/user/{id}', \App\Controllers\IndexController::class . '@user');

// router with group
Router::group(['namespace'=>'App\\Test\\WebSocket'],function (){
	// websocket router
    Router::set('ws','/a','TestController@abc'); 
    Router::set('ws','/b','TestController@bbb'); 
});

// Middleware
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

## orm

### Define the model
```php
namespace App\Model;

use One\Database\Mysql\Model;

// There is no need to specify the primary key in the model, the framework will cache the database structure
// Automatically match the primary key, automatically filter the fields in the non-table structure
class User extends Model
{
	// Define the table name corresponding to the model
    CONST TABLE = 'users';

	// define relationship
    public function articles()
    {
        return $this->hasMany('id',Article::class,'user_id');
    }
    
    // define event
    // Whether to enable automatic caching
    // ……
}
```

### Use model

- The database connection is a single column under `fpm`,
- All database operations are automatically switched to connection pool in `swoole` mode

```php
// Query a record
$user = User::find(1);

// Related query
$user_list = User::whereIn('id',[1,2,3])->with('articles')->findAll()->toArray();

// update
$r = $user->update(['name' => 'aaa']);
// or
$r = user::where('id',1)->update(['name' => 'aaa']);
// $r To influence the number of records

```

## Cache
```php
// Set cache without expiration time
Cache::set('ccc',1);

// Set the cache to expire in 1 minute
Cache::set('ccc',1,60);


Cache::get('ccc');

// or cache ccc expires 10s under tag1
Cache::get('ccc',function (){
    return 'info';
},10,['tag1']);

// Refresh all caches under tag1
Cache::flush('tag1');

```
        
## HTTP/TCP/WEBSOCKET/UDP 

Start a websocket server,
Add http service monitoring,
Add tcp service monitoring

```php

[
	 // Main server
    'server' => [
        'server_type' => \One\Swoole\OneServer::SWOOLE_WEBSOCKET_SERVER,
        'port' => 8082,
        // Event callback
        'action' => \One\Swoole\Server\WsServer::class,
        'mode' => SWOOLE_PROCESS,
        'sock_type' => SWOOLE_SOCK_TCP,
        'ip' => '0.0.0.0',
        // swoole Server setting parameters
        'set' => [
            'worker_num' => 5
        ]
    ],
    // Add listener
    'add_listener' => [
        [
            'port' => 8081,
            // Event callback
            'action' => \App\Server\AppHttpPort::class,
            'type' => SWOOLE_SOCK_TCP,
            'ip' => '0.0.0.0',
            // Set parameters for monitoring
            'set' => [
                'open_http_protocol' => true,
                'open_websocket_protocol' => false
            ]
        ],
        [
            'port' => 8083,
            // Unpacking protocol
            'pack_protocol' => \One\Protocol\Text::class,
            // Event callback
            'action' => \App\Test\MixPro\TcpPort::class,
            'type' => SWOOLE_SOCK_TCP,
            'ip' => '0.0.0.0',
            // Set parameters for monitoring
            'set' => [
                'open_http_protocol' => false,
                'open_websocket_protocol' => false
            ]
        ]
    ]
];


```

## RPC

Call the method of the remote server like the method of this project. Cross language, cross machine.

### Service
Start the rpc service. The framework has built-in rpc services for each protocol, just add it to the `action` in the above configuration file. For example: support `http` call, and support `tcp` call.
```php
// http Protocol rpc service
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
// tcp Protocol rpc service
[
    'port'          => 8083,
    'action'        => \App\Server\RpcTcpPort::class,
    'type'          => SWOOLE_SOCK_TCP,
    'pack_protocol' => \One\Protocol\Frame::class, // tcp packing protocol
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
Add specific services to rpc,
For example, there is a class `Abc`

```php 

class Abc
{
    private $a;

    public function __construct($a = 0)
    {
        $this->a = $a;
    }

    public function add($a, $b)
    {
        return $this->a + $a + $b;
    }

    public function time()
    {
        return date('Y-m-d H:i:s');
    }

    public function setA($a)
    {
        $this->a = $a;
        return $this;
    }
}

```
Add `Abc` to rpc service

```php

// Add Abc to rpc service
RpcServer::add(Abc::class);

// If you don't want to add all the methods under Abc to the rpc service, you can also specify the addition.
// Unspecified methods cannot be called by the client.
// RpcServer::add(Abc::class,'add');

// Add in groups
//RpcServer::group([
//    // The middleware can do permission verification, data encryption and decryption, etc.
//    'middle' => [
//        TestMiddle::class . '@aa'
//    ],
//    // Cache If set, when called with the same parameters, the cache information will be returned and will not be called. Unit: seconds
//    'cache'  => 10
//], function () {
//    RpcServer::add(Abc::class);
//    RpcServer::add(User::class);
//});
```

### Client call

In order to facilitate the call, we create a mapping class (one framework can be automatically generated)

```php
class ClientAbc extends RpcClientHttp {

    // rpc server address
    protected $_rpc_server = 'http://127.0.0.1:8082/';

    // The remote class is not set and the default is the current class name
    protected $_remote_class_name = 'Abc';
}
```
The remote method of calling the rpc service is the same as the method of calling this project. You can imagine this method is in your project.

```php
$abc = new ClientAbc(5);

// $res === 10
$res = $abc->add(2,3);

// Chain call $res === 105
$res = $abc->setA(100)->add(2,3);

// If the User of the above model is added to rpc
// RpcServer::add(User::class);
// The following operation results are the same as above
// $user_list = User::whereIn('id',[1,2,3])->with('articles')->findAll()->toArray();

```

The above is called through the http protocol. You can also call through other protocols. For example, Tcp protocol

```php
class ClientAbc extends RpcClientTcp {

    // rpc server address
    protected $_rpc_server = 'tcp://127.0.0.1:8083/';

    // The remote class is not set and the default is the current class name
    protected $_remote_class_name = 'Abc';
}
```

The classes `RpcClientHttp` and `RpcClientTcp` are in the framework.
You can also copy it to any other place for use.

    
## more

* [Various protocol communication examples](https://github.com/lizhichao/one-demo)
* [rpc examples](https://github.com/lizhichao/one-app/tree/test_rpc)
* [Actor examples](https://github.com/lizhichao/one-app/tree/actor_demo)

## Document

* [Document](https://github.com/lizhichao/one-doc/blob/master/SUMMARY.md)
* [Parameter validator](https://segmentfault.com/a/1190000018434298)


## TODO

* support [Workerman](https://github.com/walkor/Workerman)
* orm support [postgresql](https://www.postgresql.org/)


## My other open source projects
* [nsq client](https://github.com/lizhichao/one-nsq)
* [clickhouse tcp client](https://github.com/lizhichao/one-ck)

