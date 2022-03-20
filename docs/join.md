
# 参与 ManaPHP
ManaPHP 开源框架，非商业项目，由开源组织开发与维护，这意味着任何人都可以为其开发和进度贡献力量。
参与 ManaPHP 有多种方式：
- 代码贡献
- 文档贡献
- 官网维护
- 社区维护

## 代码贡献

1. Fork 代码库 [manaphp](https://github.com/manaphp/manaphp)
2. 发送 Pull Request 修改请求
3. 等待 ManaPHP 开发组审核和合并

> 所有的官方维护的代码均由 [manaphp](https://github.com/manaphp/manaphp) 项目统一合并后再分发给各子仓库。

## 开发步骤

### 使用 docker

推荐使用docker解决开发环境的困扰，开发组维护了[最新的镜像 manaphp/php72](https://hub.docker/manaphp/php72), 简单示例：
- 使用 docker-compose 进行服务编排

```yml
version: "2"
services:
  api_swoole:
    image: manaphp/php72:1.2
    volumes:
      - /usr/share/zoneinfo/PRC:/etc/localtime
      - ../:/var/www/html
      - /data/volumes/${COMPOSE_PROJECT_NAME}/api/runtime:/var/www/html/runtime
    command: php /var/www/html/public/index.php
    ports:
      - ${WEB_PORT}:9501
    restart: always
```

### ManaPHP 项目说明

ManaPHP 包含以下几个重要项目:
- [manaphp/framework](https://github.com/manaphp/framework)：ManaPHP框架源码
- [manaphp/app-api](https://github.com/manaphp/app-api)：纯接口应用脚手架
- [manaphp/app-user](https://github.com/manaphp/app-user)：经典MVC结构应用脚手架
- [manaphp/app-admin](https://github.com/manaphp/app-admin)：经典MVC结构，并使用Areas功能应用脚手架
- [manaphp/app-cli](https://github.com/manaphp/app-cli)：命令行应用脚手架
- [manaphp/app-cron](https://github.com/manaphp/app-cron)：定时任务应用脚手架
- [manaphp/app-ws](https://github.com/manaphp/app-ws)：WebSocket应用脚手架

## 需要了解的更多知识

- docker 基础知识
- github 如何提 PR. 推荐一个好用的工具, github desktop, 有快捷键快速提 PR.

推荐使用 wamp + phpstorm + docker, 构建全套开发环境. 有相关问题, 欢迎和开发组交流.