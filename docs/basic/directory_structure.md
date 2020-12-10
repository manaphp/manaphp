---
     title: 目录结构
---

默认的ManaPHP应用结构旨在为不同的应用提供一个好的起点。当然，你可以按照喜好管理应用的目录结构。

```bash
.
├── app/    `应用程序的核心代码，几乎所有的类都应该放在这里`
│   ├── Application.php*
│   ├── Controllers/    `所有控制器所在目录`
│   ├── Models/         `所有模型所在目录`
│   ├── Views/          `所有视图所在目录`
│   ├── Widgets/        `所有小部件所在目录`
│   └── Router.php*
├── composer.json*
├── composer.lock*
├── config/     `应用配置所在目录`
│   └── app.php*
├── data/       `应用数据所在目录，一般按照模块名称创建对应子目录`
│   └── logger/
├── docker/              
│   ├── docker-compose.yml*
├── manacli*
├── manacli.bat*
├── manacli.php*
├── public/     `所有外部可访问资源的根目录`
│   ├── favicon.ico*        
│   ├── index.php*  `应用程序的所有请求的入口点`
│   └── static/     `还包含了一些你的资源文件(如图片、Javascript和CSS)`
├── test/       `所有测试文件所在目录`
├── tmp/        `所有临时文件所在目录，一般开发工具使用`
│   └── builtin_server_router.php*
└── vendor/ `你所有的依赖包`
    ├── autoload.php*
    ├── composer/
    └── manaphp/
```
