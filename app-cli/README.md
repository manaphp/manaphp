## 简介

ManaPHP 支持三种执行方式: 传统模式(Apache/FPM)、常驻内存模式、协程模式。后两种模式具有传统框架无法比拟的性能优势。

常驻内存模式: 与传统PHP框架不同，不需要WEB服务器(Apache/PHP-FPM)，框架自带基于`Swoole\Http\Server`开发的高性能HTTP服务器。
传统的PHP应用程序中脚本结束后，所有的对象在请求后都装销毁，而ManaPHP不同，框架组件对象常驻内存，减少对象反复创建销毁的性能损失。

协程模式: 开启协程后，一个进程可以并行处理N个请求，不会像传统模式/常驻内存模式那样阻塞进程，每增加一个请求只需要增加一些内存消耗，
由于协程能并行处理，所以通常只需要配置于CPU数量一样多的进程数即可，更少的进程带来更少的CPU切换开销。

## 环境要求

* PHP >= 8.0.2

## 安装

我们提供两种安装方式。一种是通过归档文件安装，另一种则是通过composer进行安装。

### 通过归档文件进行安装
从[github](https://github.com/manaphp/app-api/archive/master.zip)下载

### composer

```bash
composer  create-project manaphp/app-cli app-cli
```

## 技术交流

官方QQ群: [554568116](http://qm.qq.com/cgi-bin/qm/qr?k=xkXnkJZXsvgMyz4d8k_pKKJgPKJm8b-T&group_code=554568116)

## License

[The MIT License (MIT)](https://mit-license.org/)