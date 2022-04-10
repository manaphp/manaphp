<?php
declare(strict_types=1);

namespace ManaPHP\Html;

use ManaPHP\Contextor\ContextInseparable;

class RendererContext implements ContextInseparable
{
    public array $sections = [];
    public array $stack = [];
    public array $templates = [];
}