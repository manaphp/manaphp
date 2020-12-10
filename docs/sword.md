---
    title: Sword模板
---

## 简介

Sword是ManaPHP提供的一个既简单又强大的模板引擎。和其他流行的PHP模板引擎不同，Sword并不限制你在模板中使用原生PHP代码。
所有的Sword模板文件都将被编译成原生的PHP代码并缓存起来，除非你的模板文件被修改了，否则不会重新编译，这就意味着Sword基本上不会给你的应用增加任何额外的负担。
Sword模板文件使用`.sword`文件扩展名。

## 模板结构
### 定义一个布局模板
我们先通过一个简单的实例认识一下Sword模板吧。首先，我们先来看一下命名为"Default"的模板文件。
由于大部分网站应用在页面之间共享某些相同的布局，所以将这个页面布局定义在一个单独的Sword模板中更便于使用。

```html
    <!-- 文件保存于: @app/Views/Layouts/Default.sword -->
   
    <html>
        <head>
            <title>App Name - @yield('title')</title>
        </head>
        <body>
            <div class="sidebar">
                @yield('sidebar')
            </div>
            <div class="content">
                @content()
            </div>
        </body>
    </html>
```

如上所见，这个文件中包含了通常见到的HTML标签。@yield指令是用来展示某个指定section所代表的内容的。@content()用于输出视图的内容。

好了，我们已经定义了一个页面布局模板了，接下来我们定义一个使用此页面布局模板的视图模板。

### 定义视图模板
由于ManaPHP的视图引擎默认是`视图`、`布局`二层结构,所以只需要定义视图模板就可以了。

@section指令正像其名字所暗示的一样，是用来定义一个模板片断(section)的；
```html
    <!-- 文件保存于： @app/Views/{Controller}/{Action}.sword -->
    
    @section('title', 'Page Title')
    
    @section('sidebar')
        <p>This is appended to the master sidebar.</p>
    @endsection
    
    Hello World!
```

## 数据输出

### 转义输出

你可以像下面这样展示**name**变量所代表的内容:

```html
Hello, {{ $name }}
```

当然，你不仅仅被局限于展示传递给模板的变量，你还可以展示PHP函数的返回值。事实上，你可以在Sword模板的双花括号表达式中调用任何PHP代码：

```html
当前时间： {{ date('Y-m-d H:i:s') }}.
```

> 注意：Sword中的{{ }}表达式的返回值将被自动传递给PHP的**htmlentities**函数进行处理，以防止XSS攻击。

### 展示未转义的数据

默认情况下，Sword的{{ }}表达式会自动通过PHP的 htmlentities 参数进行处理，以防止XSS攻击。如果你不希望自己的数据被转义，请使用如下语法：
```html
    Hello, {!! $name !!}
```
>注意：务必要对用户上传的数据小心处理。最好对内容中的所有HTML使用双花括号进行输出，这样就能转义内容中的任何HTML标签了。

### 如果数据存在就将其输出

某些时候你需要输出变量的值，但在输出前你并不知道这个变量是否被赋值了。我们将这个问题化作代码描述如下：

```html
    {{ isset($name) ? $name : 'Default' }}
```
不过，上面的表达式的确很繁复，幸好Sword提供了如下方式：
```html
    {{ $name or 'Default' }}
```
在这个实例中，如果$name变量被赋值了，它的值将被输出出来。然而，如果没有被赋值，将输出默认的单词 **Default**。

### Sword与JavaScript框架
由于很多JavaScript框架也使用"花括号”来表示一个需要在浏览器端解析的表达式，因此，你可以通过使用@符号来告知Sword模板引擎保持后面的表达式原样输出。
如下所示:

```html
    <h1>manaphp</h1>
    Hello, @{{ name }}
```
在上述实例中，@符号将被Sword移除，同时,{{ name }} 表达式将被原样输出，这就能确保在浏览器端被你的JavaScript框架解释了。

## 控制结构
Sword提供了方便的PHP流程控制方式，比如条件语句与循环，这些缩写方式提供了一种清晰，简练的PHP控制结构，同时也保持了PHP相关语法的相似性。

### If表达式
通过@if、@elseif、@else、@endif指令可以创建 **if**表达式。这些指令都有相应的PHP表达式：

```php
    @if (count($records) === 1)
        I have one record!
    @elseif (count($records) > 1)
        I have multiple records!
    @else
        I do not have any records!
    @endif
```

### 循环
通过@for、@endfor、@foreach、@endforeach、@while、@endwhile指令可以创建循环结构。这些指令都有相应的PHP表达式。
```php
    @for ($i = 0; $i < 10; $i++)
        The current value is {{ $i }}
    @endfor
    
    @foreach ($users as $user)
        <p>This is user {{ $user->id }}</p>
    @endforeach
    
    @while (true)
        <p>I'm looping forever.</p>
    @endwhile
```    
### 引入子模板
Sword提供的@partial指令允许你方便地在一个模板中引入另一个模板。默认的情况下父模板变量不能在子模板中直接使用，除非明确的引入。
```html
<div>
    @partial('Shared/Header')

    <form>
        <!-- Form Contents -->
    </form>
</div>
```
虽然被引入的子模板默认不能访问父模板中的数据，但是你可以向子模板传递数据：
```html
    @partial('Shared/Header', ['some'=>'data'])
```
>注意：你应该避免在Sword模板中使用__DIR__和__FILE__常量，因为他们将指向到模板缓存所在的位置。

## 注释

Sword还允许你在模板中添加注释。和HTML注释不同的是，Sword注释不会出现在最终生成的HTML中。
```html
{{-- 这条注释不会出现在最终生成的HTML中 --}}
```
## 扩展Sword
Sword甚至允许你声明自定义指令。你可以使用**directive**方法注册指令。当Sword编译器遇到指令时，它传递参数到回调函数。
下面这个实例将创建一个@datetime($var)指令，用于格式化$var参数：
```php
    $swordCompiler->directive('datetime',function($expression){
        return "<?php echo date('Y-m-d H:i:s',{$expression}); ?>";
     });
```