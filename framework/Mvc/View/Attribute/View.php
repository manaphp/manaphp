<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class View
{
    public function __construct(public ?string $vars = null, public ?string $template = null)
    {

    }

    public function getVars(): ?string
    {
        return $this->vars;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }
}