<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

interface MessageMakerInterface
{
    public function make(): mixed;
}