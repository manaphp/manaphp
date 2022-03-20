<p align="center">高性能 • 轻量级 • 命令行</p>

<p align="center">
<img src="https://img.shields.io/badge/platform-linux%20%7C%20win%20%7C%20osx-lightgrey.svg">
</p>

## ManaPHP 是什么

ManaPHP 秉承 **"普及PHP协程, 促进PHP发展"** 的理念而创造，采用Swoole扩展作为底层引擎，围绕常驻内存的环境而设计， 提供了 Console / Http 开发所需的众多开箱即用的组件。

ManaPHP追求简单、试图让更多开发者以更低学习成本享受到Swoole带来的高性能与全新的编程体验。

## 与传统 MVC 框架比较

ManaPHP 支持三种执行方式: 传统模式(Apache/FPM)、常驻内存模式、协程模式。后两种模式具有传统框架无法比拟的性能优势。

常驻内存模式: 与传统PHP框架不同，不需要WEB服务器(Apache/PHP-FPM)，框架自带基于`Swoole\Http\Server`开发的高性能HTTP服务器。
传统的PHP应用程序中脚本结束后，所有的对象在请求后都装销毁，而ManaPHP不同，框架组件对象常驻内存，减少对象反复创建销毁的性能损失。

协程模式: 开启协程后，一个进程可以并行处理N个请求，不会像传统模式/常驻内存模式那样阻塞进程，每增加一个请求只需要增加一些内存消耗，
由于协程能并行处理，所以通常只需要配置于CPU数量一样多的进程数即可，更少的进程带来更少的CPU切换开销。

## 与其他基于Swoole框架比较

* ManaPHP框架非常轻量化，架构简单，源码可读性非常强，容易掌握与定制。
* 开发方式与传统MVC框架完全一致，用户无需了解Swoole即可开发。
* 框架集成了众多开箱即用的组件，方便快速开发。
* 目前唯一不用修改代码就可以同时支持传统模式(Apache/FPM)、常驻内存模式、协程模式的框架，用户可渐进式学习、选择合适自己团队的模式。
* 采用Swoole原生协程与最新的PHP Streams一键协程化技术。

## 框架定位

在其他Swoole框架都定位大中型团队、庞大的PHP应用集群的时候，ManaPHP决定推动技术的普及，我们定位于众多的中小型企业、创业公司，
我们将Swoole的众多功能封装起来，用简单的方式呈现给用户，让更多的初中级程序员也可以打造高并发系统，让Swoole不再只是高级程序员的专属工具。

## 核心特征

* 命令行：封装了命令行开发基础设施，可快速开发控制台程序；
* HTTP：常驻内存 + 协程+传统MVC框架相似的使用方法；
* 高性能: 极简架构 + Swoole引擎 + 协程，超过`Phalcon`,`Yaf`这类C扩展框架的性能；
* 服务器: 框架自带服务器，无需Apache/PHP-FPM等外置容器；
* 协程：采用Swoole原生协程与最新的PHP Streams一键协程化技术；
* 连接池: Db/Redis组件默认使用连接池;
* 长连接: 按进程保持长连接，支持Db/Redis;
* 依赖注入: 参考Phalcon及同类框架，实现了简易好用的IoC；
* 组件： 基于组件的框架结构，并集成了大量开箱即用的组件；
* 中间件：注册方便，能更好的对请求进行过滤和处理；
* 路由：底层全正则实现，性能高，配置简单；
* 视图： 使用类似Blade的高效模板引擎，使用预编译技术，比原生PHP引擎速度更快；
* 自动加载: 支持Composer,可以很方便的使用第三方库;

## 开发文档

ManaPHP开发指南:

- [docs](docs/)

## 环境要求

* PHP >= 8.0.2
* Swoole >= 4.6.7

## 快速开始

推荐使用 [composer](https://www.phpcomposer.com/) 安装。

```
composer create-project manaphp/app-api --prefer-dist
```

启动服务器:

接下来启用`http`服务器

```
php /var/www/html/public/index.php
```

访问测试(新开一个终端):

```
curl http://127.0.0.1:9501/api
```

## 下载

[ManaPHP 发行版本](https://github.com/manaphp/manaphp/releases)

## 技术交流

官方QQ群: [554568116](http://qm.qq.com/cgi-bin/qm/qr?k=xkXnkJZXsvgMyz4d8k_pKKJgPKJm8b-T&group_code=554568116)

## License

[The MIT License (MIT)](https://mit-license.org/)