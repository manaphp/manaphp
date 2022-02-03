<?php
declare(strict_types=1);

namespace ManaPHP\Http\Acl;

interface BuilderInterface
{
    public function getControllers(): array;

    public function getActions(string $controller): array;
}