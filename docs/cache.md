---
    title: 缓存
---

### 简介

如果要对一个网站或应用程序进行优化，可以说缓存的使用是最快也是效果最明显的方式。一般而言，我们会把一些常用的，或者需要花费大量资源或时间产生的数据缓存起来，使得后续的使用更加快速。
ManaPHP框架提供`ManaPHP\Cache`组件实现缓存功能。

### 访问缓存

如下代码是一个典型的数据缓存使用模式：
```php
    //尝试从缓存中取回$data
    $data = $cache->get($key);
    
    if($data === false){
        //$data在缓存中没有找到，则重新计算它的值
        $data=complex_compute();
        
        //将$data存放到缓存中供下次使用
        $cache->set($key,$data, $ttl);
    }
    
    //这儿$data就可以使用了
   var_dump($data);
```

### 缓存删除

```php
    $cache->delete("key");
```

### 检查缓存是否存在

```php
    if ($cache->exists("key")) {
        echo $cache->get("key");
    } else {
        echo "Cache does not exists!";
    }
```

### 设置缓存

```php
    $cache->set('key','value',$ttl);
```