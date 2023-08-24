<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Mailing\Mailer\Adapter\Smtp;

class Mailer
{
    #[Inject] protected MakerInterface $maker;

    public function __invoke(array $parameters, ?string $id): mixed
    {
        return $this->maker->make(Smtp::class, $parameters, $id);
    }
}