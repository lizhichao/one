# One - 一个极简的框架

## 主要功能特点

1. 独立的路由模块
    - 支持中间件
    - 支持路由分组
    - 支持restful
    - 支持在http,webSocket,tcp...下运行
    - 除restful外可自定义任何方法
    - 接口缓存（可对整个接口缓存 中间件依然会执行）
    - 采用hashMap储存路由信息，超高的解析性能。比一般的正则表达式解析至少高出一个数量级,路由信息大小不影响解析速度。
    
2. orm（数据库模型）
    - 自动sql注入过滤
    - 自动过滤非表结构的字段
    - 各种sql链式操作，IDE友好提示
    - 数据表关系映射(hasOne,hasMany)
    - 自动化缓存(保持与数据库同步更新)
    - sql日志模板（可为后期优化提供全面的分析数据）
    
3. 日志
    - 自动记录每条日志产生的文件名和行号
    - 自动增加request_id全程跟踪
    - 可高度自定义
    
4. 缓存(redis,file)
    - 支持tag

5. session(redis,file)
    
### 以下特点仅在 swoole 运行下才有

6. 数据库连接池
   - 全程自动化完成(获取连接对象，放回连接对象到池中)。当代码运行在swoole下时自动使用连接池.
   
   - 列外：  
        - 你手动pop出一个pdo原生对象，需要自己push放回池中
   
7. redis连接池
   - 全程自动化完成(获取连接对象，放回连接对象到池中)
   
   - 列外：  
       - 你手动pop出一个Redis原生对象，需要自己push放回池中

8. 常驻内存服务器
    - http服务器
    - webSocket服务器
    - tcp服务器
    - ...
    - 各种混合协议通讯
    - 提供一对多关系绑定(用户id和fd绑定, 群id和用户id绑定...)

9. 服务器之间内存共享
    - 所有操作均为原子操作(不用考虑并发数据不一致问题)
    - 性能和redis相当
    - 支持任意层级数据操作(set a.b.c.d 1)
    - 自己可以轻松扩展

10. 后台进程Task任务处理


[文档地址](https://www.kancloud.cn/vic-one/php-one/826876)

[使用列子-DEMO](https://github.com/lizhichao/one-demo)

QQ交流群: 731475644

## 安装

```shell
composer create-project lizhichao/one-app
```

