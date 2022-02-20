<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class AcceptVerbs
{
    public array $verbs;

    public function __construct(array $verbs)
    {
        $this->verbs = $verbs;
    }
}