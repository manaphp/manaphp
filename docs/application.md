---
    title: 请求生命周期
---

## 简介

当我们使用现实世界中的任何工具时，如果理解了该工具的工作原理，那么用起来就会得心应手，应用开发也是如此。
当你理解了开发工具如何工作，用起来就会游刃有余。

这篇文档的目的就是从更高层面向你阐述ManaPHP框架的工作原理。通过对框架更全面的了解，一切都不再那么神秘，
你将会更加自信的构建应用。如果你不能马上理解所有这些条款，不要失去信心！先试着掌握一些基本的东西，
你的知识水平将会随着对文档的探索而不断提升。

## 生命周期概览

### 初始化并创建Application实例

ManaPHP应用的所有请求入口都是`public/index.php`文件，所有请求都会被web服务器(Apache/Nginx/Swoole)导向这个文件。
`index.php`文件包含的代码并不多，但是，这里是加载框架其他部分的起点。

`index.php`文件载入Composer生成的自动加载设置，然后从创建一个`Application`实例。

### 执行Application的main函数

接下来，请求被发送到Application，它是所有请求都要经过的中央处理器，现在，就让我们聚焦在位于`app/Application.php`的Application。
Application类继承自`ManaPHP\Rest\Application`或`ManaPHP\Mvc\Application`类。

### 载入.env文件中的配置信息

所有与环境有关的配置信息都存放在项目的根目录下的`.env`文件中。

### 初始化configure实例

configure实例通过`config.php`及`.env`配置文件中的内容做初始化

### 注册configure中的所有服务及插件

### 分发请求

一旦应用被启动并且所有的组件被注册，请求将交给路由器进行分发，路由器将会分发请求到控制器。

### 聚焦组件

组件是启动ManaPHP应用中最关键的部分，应用被实例创建后，组件被注册，请求被交给启用后的应用处理，整个过程就是这么简单！

对ManaPHP应用如何通过组件构建和启动有一个牢固的掌握非常有价值。

## 事件

ManaPHP在配置初始完成后，就会进入请求处理周期，请求处理是由事件驱动起来的，可在不同位置增加自己的应用逻辑。

* request:begin 请求处理开始，主要是做一些不依赖其他组件的逻辑
* request:authenticate 用户身份辨别
* request:authorize  用户权限检查
* request:validate 做请求数据做验证
* request:ready 请求处理准备好
* request:invoke 开始动作处理
* request:invoked 完成动作处理
* request:end 请求结束，主要是做一些不依赖其他组件的逻辑

## 应用分类

ManaPHP支持两类应用的构建，一是Http应用程序，二是命令行应用程序。

同时，ManaPHP为了满足不同需求，提供了多种应用配置，分别如下：

* app-cli 命令行程序
* app-cron 定时任务程序
* app-api 纯接口服务程序
* app-user 经典MVC结构应用程序
* app-admin 经典的MVC结构应用程序，并使用Areas功能，方便功能归类
