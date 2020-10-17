<?php

namespace Tests;

use ManaPHP\Security\HtmlPurifier;
use PHPUnit\Framework\TestCase;

class SecurityHttpPurifierTest extends TestCase
{
    public function test_purify()
    {
        $httpPurifier = new HtmlPurifier();

//最简单最常用的测试代码
        $source = <<<EOT
<script>alert(“xss”)</script>
EOT;
        $wanted = <<<EOT

EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

//使用javascript中的fromCharCode函数，用于将ascii转化成字符
        $source = <<<EOT
<script>String.fromCharCode(97,108,101,114,116)(String.fromCharCode(88,83,83))</script>
EOT;
        $wanted = <<<EOT

EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<ScRiPt>. alert(“xss”);</ScRiPt>
EOT;
        $wanted = <<<EOT

EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

//过滤时使用递归算法才能彻底过滤这种情况
        $source = <<<EOT
<Scriscriptpt>alert(“xss”);<scriScriptpt>
EOT;
        $wanted = <<<EOT

EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<h1 onload="alert(‘xss’)">manaphp</h1>
EOT;
        $wanted = <<<EOT
<h1>manaphp</h1>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<img src="a" onerror="javascript.:alert('xss')">
EOT;
        $wanted = <<<EOT
<img src="a">
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<img src=javascript::alert('xss')>
EOT;
        $wanted = <<<EOT
<img>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<h1>manaphp</h1>
EOT;
        $wanted = <<<EOT
<h1>manaphp</h1>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<script src="https://cdn.test.com/jquery/1.12.4/jquery.min.js"></script>
EOT;
        $wanted = <<<EOT

EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<script src="https://cdn.test.com/jquery/1.12.4/jquery.min.js"></script><h1>manaphp</h1>
EOT;
        $wanted = <<<EOT
<h1>manaphp</h1>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<a href="javascript:alert(1);">manaphp</a>
EOT;
        $wanted = <<<EOT
<a>manaphp</a>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<a href="http://www.baidu.com/">manaphp</a>
EOT;
        $wanted = <<<EOT
<a href="http://www.baidu.com/">manaphp</a>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));

        $source = <<<EOT
<a href="#" onclick="hello()"><i>Hello</i></a>
EOT;
        $wanted = <<<EOT
<a href="#"><i>Hello</i></a>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));
    }

    public function test_purify_normal()
    {
        $httpPurifier = new HtmlPurifier();

        $source = <<<EOT
<p>manaphp</p>
EOT;
        $wanted = <<<EOT
<p>manaphp</p>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));
        $source = <<<EOT
<div><div></div></div>
EOT;
        $wanted = <<<EOT
<div><div></div></div>
EOT;
        $this->assertEquals($wanted, $httpPurifier->purify($source));
    }
}