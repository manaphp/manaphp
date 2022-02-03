<?php
declare(strict_types=1);

namespace ManaPHP\Html;

use ManaPHP\Coroutine\Context\Inseparable;

class RendererContext implements Inseparable
{
    public array $sections = [];
    public array $stack = [];
    public array $templates = [];
}