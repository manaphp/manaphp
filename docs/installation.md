---
    title: 安装
---

## 运行环境要求

需要保证运行环境满足以下要求：
* PHP >= 8.0
* Swoole >= 4.6.7

当然，[manaphp/php80](https://hub.docker.com/r/manaphp/php80)已满足所有这些要求，所以我们强烈推荐使用[docker](https://www.docker.com/)作为ManaPHP运行环境。

## 安装ManaPHP

使用ManaPHP之前，确保机器上已经安装了[composer](https://getcomposer.org/)。

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

### 本地开发服务器

如果您想使用PHP内置的开发服务器，可以使用manacli的`serve`命令：
```
php manacli.php serve
```

该命令将会在本地启动开发服务器[http://127.0.0.1:9501/api](http://127.0.0.1:9501/api)

> 如果是windows环境，那么可以使用`manacli serve`指令

## 应用配置
ManaPHP提供了灵活的配置功能，根据不同环境有所不同的在`.env`中配置，通用的在`config/app.php`中配置。

### public目录

安装完ManaPHP后，需要将HTTP服务器的Web根目录指向`public`目录，该目录下的`index.php`文件将作为前端控制器，所有HTTP请求都会通过该文件进入应用。

### 配置文件

ManaPHP框架的所有配置文件都放在`config`目录下。

### 目录权限

安装完ManaPHP后，需要配置一些目录的读写权限: `data`和`tmp`目录应该是可写的，如果你使用docker做为开发环境，这些权限已经设置好了。

### 应用KEY

按下来要做的事情就是应用的key(`MASTER_KEY`)设置为一个随机字符串，通常，该字符串应是32位长，通过`.env`文件中的`MASTER_KEY`进行配置，

如果你还没有将`.env.example`文件重命名为`.env`，现在立即这样做。

### 更多配置

ManaPHP几乎不再需要其它任何配置就可以正常使用了，但是你最好看看`config/app.php`文件，其中包含了一些基于应用可能需要进行改变的配置，比如`timezone`。

## WEB服务器配置

### Apache

框架中自带的`public/.htaccess`文件支持URL中隐藏`index.php`，如果你的ManaPHP应用使用Apache作为服务器，需要先确保Apache启用了`mod_rewrite`模块以支持`.htaccess`
解析。

### Nginx

如果你使用的是Nginx，使用如下站点配置指令就可以支持URL美化：
```
location / {
   try_files $uri $uri/ /index.php?_url=$uri&$args;
}
```
当然，使用docker的话，以上配置已经为你配置好以支持URL美化。

## Swoole配置

### 安装Swoole扩展
pecl 在 php/bin 目录，国内 pecl 安装 swoole 有时很慢，如果无法忍受，可选择 [编译](https://wiki.swoole.com/wiki/page/6.html)安装。

```
pecl install swoole
```
### 确认安装成功

启用manaphp服务器:
```
php /var/www/html/public/index.php
```

### 访问测试(新开一个终端)
```
curl http://127.0.0.1:9501/api
```

### 增加 Nginx 反向代理

反向代理主要负责静态文件处理和负载均衡，直接复制下面的配置。
```
server {
    server_name www.test.com;
    listen 80; 
    root  /var/www/html/public;

    location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "keep-alive";
        proxy_set_header Host $http_host;
        proxy_set_header Scheme $scheme;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        if (!-f $request_filename) {
             proxy_pass http://127.0.0.1:9501;
        }
    }
}
```
> 在ManaPHP中通过$this->request->getClientIp()`来获取客户端的真实IP。

### Swoole IDE 自动补全 (非必须)

这个不是必须安装的，只是能方便在需要写一些原生 Swoole 时，能让 IDE 自动补全，很方便的一个工具，推荐安装。

[>> 到 GitHub 下载 swoole-ide-helper-phar <<](https://github.com/wudi/swoole-ide-helper)