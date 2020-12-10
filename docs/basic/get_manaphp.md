---
     title: 获取ManaPHP
---

### 官方网站

获取ManaPHP的方式很多，官方网站[http://www.manaphp.com](http://www.manaphp.com)是最好的下载和文档获取来源。

### 源代码托管平台

* github [https://github.com/manaphp/manaphp](https://github.com/manaphp/manaphp)
* oschina [https://gitee.com/manaphp/manaphp](https://gitee.com/manaphp/manaphp)

### 通过composer create-project

你可以在终端中通过composer的`create-project`命令来安装ManaPHP应用：
```
composer create-project --prefer-dist manaphp/app-api app-api
```

### 应用程序类型
ManaPHP为了满足不同需求，提供了多种应用配置，分别如下：

* [app-cli](https://github.com/manaphp/app-cli) 控制台应用程序
* [app-cron](https://github.com/manaphp/app-cron) 定时任务应用程序
* [app-api](https://github.com/manaphp/app-api) API服务应用程序
* [app-user](https://github.com/manaphp/app-user) 经典MVC结构应用程序
* [app-admin](https://github.com/manaphp/app-admin) 经典的MVC结构应用程序，并使用Areas功能，方便功能归类
* [app-rpc](https://github.com/manaphp/app-rpc) RPC服务器应用程序
* [app-ws](https://github.com/manaphp/app-ws)  WebSocket服务器应用程序
