> 在使用很多框架的时候一直感觉不是足够简单，看基于swoole的那些框架设计的更复杂。
> 于是设计框架 - one  
> 这是一个极简灵活的[常驻内存]框架,可运行在apache/php-fpm或者swoole下。    
> 风格统一，没有各种让开发者费解的东西，在apache/php-fpm和普通的mvc框架没有区别，不用改任何代码可直接运行在swoole异步、协程的环境下，即使你是新手也能轻松上手。
> 如果你使用过`thinkPHP`,`laravel`,`yii`花5分钟就能熟练掌握。
> 如果你要一探究竟，框架代码量非常少，各模块是独立的，适合二次开发和扩展

[文档地址](https://www.kancloud.cn/vic-one/php-one/826876)

[使用列子-DEMO](https://github.com/lizhichao/one-demo)

QQ交流群: 731475644

### 特点:

- 框架本身消耗非常少，在`apache/php-fpm`下框架消耗时间保持在`1ms`左右。
- 灵活的路由和中间件，支持在各种协议运行`tcp`、`http`、`websocket`...
- 灵活的orm，包含常用的`curd`和关系处理`hasOne,hasMany`以及自动化缓存机制可让项目绝大部分流量走缓存,并保持与数据库同步自动更新
- 框架内可同时处理`tcp`、`http`、`websocket`...多种协议请求，可轻松实现各协议消息互通
- `globalData` 内存共享支持任意层级的原子操作，数据结构可自己扩展,可轻松实现分布式内存共享。
- 自动使用数据库连接池（`apache/php-fpm`下一个连接）
- `trance_id|request_id` 方便bug追踪调用链追踪

### 安装

```shell
composer create-project lizhichao/one-app
```

#### hello word

```php
// 添加路由 App/Config/router.php
Router::get('/', \App\Controllers\IndexController::class . '@index');

// 控制器代码 App/Controllers/IndexController.php
class IndexController extends Controller
{
    public function index()
    {
        return 'hello word';
    }
}

// Apache/fpm下启动  设置 App/public 为根目录

// 常驻内存启动 php App/swoole.php

// 访问 http://{host}/hello

```

### demo
[one-demo](https://github.com/lizhichao/one-demo)
