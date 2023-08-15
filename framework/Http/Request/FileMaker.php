<?php
declare(strict_types=1);

namespace ManaPHP\Http\Request;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;

class FileMaker implements FileMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(array $parameters): mixed
    {
        return $this->maker->make(File::class, $parameters);
    }
}