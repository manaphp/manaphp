---
    title: 模型
---

## 简介
ManaPHP为了方便数据表的维护实现了`Model`类。

## 定义模型类
定义一个User模型类：

```php
    namespace App\Models;
    use ManaPHP\Db\Model;
    
    class User extends Model
    {
        public $user_id;
        public $user_name;
        public $created_time;
    }
```

## 绑定数据表
模型会自动对应数据表，模型类的名字是驼峰式命名规则转换后的数据表名称，并且首字母大写，例如：

| 模型名称 | 约定对应数据表 |
| -------- | -------------- |
| User     | user           |
| UserType | user_type      |

如果模型与表名不符和上述规则，那么需要明确指定模型对应的数据表名称，方法如下：

```php
    namespace Application\Home\Models;
    use ManaPHP\Mvc\Model;
    
    class User extends Model
    {
        public function getSource($context = null){
            return 'users';
        }
    }
```

## 绑定数据库服务
模型默认使用的服务是`db`,可以使用下面的方法改变所使用的数据库服务：

```php
    namespace Application\Home\Models;
    use ManaPHP\Mvc\Model;

    class User extends Model
    {
        public function getDb($context = null){
            return 'db_user';
        }
    } 
```
## 模型使用方式
模型类可以使用静态调用或者实例化调用两种方式，例如：

```php
    //静态调用
    User::get(1);

    //实例化调用
    $user = new User();
    $user->user_id = $user_id;
    $user->user_name = $user_name;
    $user->created_time = time();
    $user->create();
```

## 新增
新增数据有多种方式。
### 创建单条记录
第一种是实例化模型对象后通过字段赋值再创建：

```php
    $user = new User();

    $user->user_id = 1;
    $user->user_name ='manaphp';
    $user->created_time = time();

    $user->create();
```
>注意：不能直接在实例化的时候传入数据,否则创建不会成功。

### 获取自增ID
如果要获取新增记录的自增ID，可以使用下面的方法：

```php
    $user = new User();

    $user->user_name = 'manaphp';
    $user->created_time = time();

    $user->create();

    //获取自增ID
    echo $user->user_id;
```

>注意：这里其实是获取数据表的主键，如果你的主键不是`user_id`,而是`id`的话，其获取自增ID就变成这样：

```php
    $user = new User();

    $user->user_name = 'manaphp';
    $user->created_time = time();

    $user->create();

    //获取自增ID
    echo $user->id;
```
### 创建多条记录
通过模型不能一次创建多条记录，如果确实需要，可以通过多次创建实例的方式，如下：

```php
    //创建第一个实例
    $user = new User();

    $user->user_name ='manaphp';
    $user->created_time = time();

    $user->create();

    //创建第二个实例
    $user = new User();

    $user->user_name ='admin';
    $user->created_time = time();

    $user->create();
```

## 更新

### 通过实例方法更新记录

第一种是通过字段赋值再更新:

```php
    //查找记录
    $user = User::get(1);

    //更新字段
    $user->user_name = 'mana';

    //更新记录
    $user->update();
```

### 通过静态方法更新记录
通过updateAll方法可以批量更新数据，例如：
```php
    User::updateAll(['enabled'=>1],['user_name'=>'manaphp']);
```
>注意：如果通过静态方法更新记录，那么就无法使用模型的事件机制。

>警告! 请不要使用同一个实例做多次更新，会导致部分重复数据不再更新，正确的方式 
应是先查询后再更新或使用静态方法更新。

## 删除
### 通过实例方法删除记录
```php
    $user = User::get(1);
    $user->delete();
```
### 通过静态方法删除记录
```php
    User::deleteAll(['enabled'=>0]);
```

## 查询
### 查询单条记录
first函数簇用于查询单条记录。如果记录不存在，返回FALSE。例如：

```php

    //通过主键查询
    User::first($user_id);
```
### 查询多条记录
findAll函数簇用于查询多条记录。如果记录不存在，返回空数组。例如：

```php
    //通过主键查询
    User::all(['enabled' => 1]);
```

### 通过构造器查询
可以通过query函数返回的查询构造器构建复杂的条件查询。例如：

```php
    $users = User::where(['user_id' => $user_id])
            ->columns('user_id, user_name')
            ->fetch();
```

## 聚合
可以通过模型调用聚合函数进行查询，支持的方法如下:

|  方法 |   说明   |
| ----- | -------- |
| count | 统计数量 |
| max   | 最大值   |
| min   | 最小值   |
| avg   | 平均值   |
| sum   | 求和     |

```php
    echo User::count(['user_id'=>1]);

```

[PDO]: http://php.net/manual/en/pdo.prepared-statements.php
[date]: http://php.net/manual/en/function.date.php
[time]: http://php.net/manual/en/function.time.php
[Traits]: http://php.net/manual/en/language.oop5.traits.php
