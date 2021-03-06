---
    title: 配置
---

## 简介

ManaPHP的所有配置文件都存放在`config`目录下。

## 环境配置

基于应用运行的环境不同设置不同的配置值能够给我们开发带来极大的方便，比如，我们通常在本地和线上环境配置不同的数据库连接，这一机制在ManaPHP中很容易实现。

ManaPHP使用类似Vance Lucas 开发的PHP库 [DotEnv](https://github.com/vlucas/phpdotenv) 来实现这一机制,在新安装的ManaPHP中，根目录下有一个`.env.example`文件，
如果ManaPHP是通过Composer安装的，那么该文件已经被复制一份为`.env`，否则的话你要自己手动复制一份该文件。

## 获取环境变量配置值

在应用每次接受请求时，`.env`中列出的所有配置及其值都会被载入到Dotenv组件中，然后你就可以在应用中通过辅助函数`env`来获取这些配置值。实际上，如果你去查看ManaPHP的配置文件，
就会发现很多地方已经在使用这个辅助函数了:
```php
'debug' => env('APP_DEBUG', false),
```
传递到env函数的第二个参数是默认值，如果环境变量没有被配置将会是该默认值。

不要把`.env`文件提交到源码控制(`svn`或`git`等)中，因为每个使用你的应用的开发者/服务器可能要求不同的环境配置。

如果你是在一个团队中进行开发，你需要将`.env.example`文件随你的应用一起提交到源码控制中：将一些配置值以占位符的方式放置在`.env.example`文件中，这样其他开发者就会很清楚运行你的
应用需要配置哪些环境变量。

## 判断当前应用环境

当前应用环境由`.env`文件中的`APP_ENV`变量决定，你可以通过`$this->configure->env`属性来访问其值：
```php
$env=$this->configure->env;
```

## 访问配置值

应用的所有配置都有归类，如果是`params`节中的内容可以通过全局辅助函数`param_get`在应用的任意位置访问配置值，当配置项没有被配置值，返回第二个参数的值。

```php
//'params' => ['a' => [
//        'b' => '1000'
//    ]

params_get('a.b', 0);
```
其他的配置都可以通过`$this->configure`实例获取。
