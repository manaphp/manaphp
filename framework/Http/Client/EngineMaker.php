<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class EngineMaker implements EngineMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(string $engine): mixed
    {
        return $this->maker->make($engine);
    }
}