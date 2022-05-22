<?php

namespace Tests;

use ManaPHP\Html\Renderer\Engine\Sword\Compiler;
use PHPUnit\Framework\TestCase;

class HtmlRendererEngineSwordCompilerTest extends TestCase
{
    /**
     * @var \ManaPHP\Renderer\Engine\Sword\Compiler
     */
    public $sword;

    public function setUp()
    {
        parent::setUp();

        $this->sword = new Compiler();
    }

    public function test_Append()
    {
        $source = <<<'EOT'
@append
EOT;
        $compiled = <<<'EOT'
<?php $renderer->appendSection(); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Break()
    {
        $source = <<<'EOT'
@break
EOT;
        $compiled = <<<'EOT'
<?php break; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@break(false)
EOT;
        $compiled = <<<'EOT'
<?php if(false) break; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Comments()
    {
        $source = <<<'EOT'
{{-- comments --}}
EOT;
        $compiled = <<<'EOT'
<?php /* comments */ ?> 
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Continue()
    {
        $source = <<<'EOT'
@continue
EOT;
        $compiled = <<<'EOT'
<?php continue; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@continue(false)
EOT;
        $compiled = <<<'EOT'
<?php if(false) continue; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_EscapedEchos()
    {
        $source = <<<'EOT'
Welcome, {{ $name or ManaPHP }} !
EOT;
        $compiled = <<<'EOT'
Welcome, <?php echo e(isset($name) ? $name : ManaPHP); ?> !
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_RawEchos()
    {
        $source = <<<EOT
{!! '\'"<>&' !!}
EOT;

        $compiled = <<<EOT
<?php echo '\'"<>&'; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Else()
    {
        $source = <<<'EOT'
@else
EOT;
        $compiled = <<<'EOT'
<?php else: ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Elseif()
    {
        $source = <<<'EOT'
@elseif (true)
EOT;
        $compiled = <<<'EOT'
<?php elseif(true): ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_EndFor()
    {
        $source = <<<'EOT'
@endfor
EOT;
        $compiled = <<<'EOT'
<?php endfor; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_EndForeach()
    {
        $source = <<<'EOT'
@endforeach
EOT;
        $compiled = <<<'EOT'
<?php endforeach; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Can()
    {
        $source = <<<'EOT'
@can('home:index:index')
EOT;
        $compiled = <<<'EOT'
<?php if ($di->authorization->isAllowed('home:index:index')): ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Allow()
    {
        $source = <<<'EOT'
@allow('m:c:a',<ul><ul>)
EOT;
        $compiled = <<<'EOT'
<?php if ($di->authorization->isAllowed('m:c:a')): ?><ul><ul><?php endif ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Cannot()
    {
        $source = <<<'EOT'
@cannot('home:index:index')
EOT;
        $compiled = <<<'EOT'
<?php if (!$di->authorization->isAllowed('home:index:index')): ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_EndCan()
    {
        $source = <<<'EOT'
@endcan
EOT;
        $compiled = <<<'EOT'
<?php endif; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_EndCannot()
    {
        $source = <<<'EOT'
@endcannot
EOT;
        $compiled = <<<'EOT'
<?php endif; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Endif()
    {
        $source = <<<'EOT'
@endif
EOT;
        $compiled = <<<'EOT'
<?php endif; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_EndSection()
    {
        $source = <<<'EOT'
@endSection
EOT;
        $compiled = <<<'EOT'
<?php $renderer->stopSection(); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_EndWhile()
    {
        $source = <<<'EOT'
@endwhile
EOT;
        $compiled = <<<'EOT'
<?php endwhile; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_For()
    {
        $source = <<<'EOT'
@for ($i = 0; $i < 10; $i++)
EOT;
        $compiled = <<<'EOT'
<?php for($i = 0; $i < 10; $i++): ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Foreach()
    {
        $source = <<<'EOT'
@foreach ($users as $user)
EOT;
        $compiled = <<<'EOT'
<?php $index = -1; foreach($users as $user): $index++; ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_If()
    {
        $source = <<<'EOT'
@if (count($lists) > 1)
EOT;
        $compiled = <<<'EOT'
<?php if(count($lists) > 1): ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Include()
    {
        $source = <<<'EOT'
@include('footer')
EOT;
        $compiled = <<<'EOT'
<?php $renderer->partial('footer') ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@include('footer',['p1'=>1,'p2'=>2])
EOT;
        $compiled = <<<'EOT'
<?php $renderer->partial('footer',['p1'=>1,'p2'=>2]) ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Partial()
    {
        $source = <<<'EOT'
@include('footer')
EOT;
        $compiled = <<<'EOT'
<?php $renderer->partial('footer') ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@include('footer',['p1'=>1,'p2'=>2])
EOT;
        $compiled = <<<'EOT'
<?php $renderer->partial('footer',['p1'=>1,'p2'=>2]) ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Section()
    {
        $source = <<<'EOT'
@section('scripts')
EOT;
        $compiled = <<<'EOT'
<?php $renderer->startSection('scripts'); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Stop()
    {
        $source = <<<'EOT'
@stop
EOT;
        $compiled = <<<'EOT'
<?php $renderer->stopSection(); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_While()
    {
        $source = <<<'EOT'
@while (true)
EOT;
        $compiled = <<<'EOT'
<?php while(true): ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Yield()
    {
        $source = <<<'EOT'
@yield('scripts')
EOT;
        $compiled = <<<'EOT'
<?php echo $renderer->getSection('scripts'); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@yield('scripts','')
EOT;
        $compiled = <<<'EOT'
<?php echo $renderer->getSection('scripts',''); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Layout()
    {
        $source = <<<'EOT'
@layout(false)
EOT;
        $compiled = <<<'EOT'
<?php $view->disableLayout(); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@layout('table')
EOT;
        $compiled = <<<'EOT'
<?php $view->setLayout('table'); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Content()
    {
        $source = <<<'EOT'
@content
EOT;
        $compiled = <<<'EOT'
<?php echo $view->getContent(); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Php()
    {
        $source = <<<'EOT'
@php
EOT;
        $compiled = <<<'EOT'
<?php 
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@php($di->router->getActionName())
EOT;
        $compiled = <<<'EOT'
<?php $di->router->getActionName(); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_EndPhp()
    {
        $source = <<<'EOT'
@endphp
EOT;
        $compiled = <<<'EOT'
 ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Widget()
    {
        $source = <<<'EOT'
@widget('ad')
EOT;
        $compiled = <<<'EOT'
<?php echo widget('ad'); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@widget('ad',['category_id'=>1])
EOT;
        $compiled = <<<'EOT'
<?php echo widget('ad',['category_id'=>1]); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Url()
    {
        $source = <<<'EOT'
@url('/')
EOT;
        $compiled = <<<'EOT'
<?php echo url('/'); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
@url('/',['id'=>100])
EOT;
        $compiled = <<<'EOT'
<?php echo url('/',['id'=>100]); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Asset()
    {
        $source = <<<'EOT'
@asset('/app.js')
EOT;
        $compiled = <<<'EOT'
<?php echo asset('/app.js'); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_literal()
    {
        $source = <<<'EOT'
@{{abc}}
EOT;
        $compiled = <<<'EOT'
{{abc}}
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_Flash()
    {
        $source = <<<'EOT'
@flash()
EOT;
        $compiled = <<<'EOT'
<?php $di->flash->output() ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_json()
    {
        $source = <<<'EOT'
@json(get_included_files())
EOT;
        $compiled = <<<'EOT'
<?php echo json_encode(get_included_files(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ;?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_debugger()
    {
        $source = <<<'EOT'
@debugger()
EOT;
        $compiled = <<<'EOT'
<?php if($di->has("debuggerPlugin")){?><div class="debugger"><a target="_self" href="<?php echo $di->debuggerPlugin->getUrl(); ?>">Debugger</a></div><?php }?> 
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_csrf_token()
    {
        $source = <<<'EOT'
{{csrf_token()}}
EOT;
        $compiled = <<<'EOT'
<?php echo csrf_token() ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_input()
    {
        $source = <<<'EOT'
{{input('id')}}
EOT;
        $compiled = <<<'EOT'
<?php echo e(input('id')); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));

        $source = <<<'EOT'
{{input('id','manaphp')}}
EOT;
        $compiled = <<<'EOT'
<?php echo e(input('id','manaphp')); ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }

    public function test_date()
    {
        $source = <<<'EOT'
@date(1)
EOT;
        $compiled = <<<'EOT'
<?php echo date('Y-m-d H:i:s', 1) ?>
EOT;
        $this->assertEquals($compiled, $this->sword->compileString($source));
    }
}