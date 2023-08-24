<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Logging\Logger\Adapter\File;

class Logger
{
    #[Inject] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        return $this->maker->make(File::class, $parameters, $id);
    }
}