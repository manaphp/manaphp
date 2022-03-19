<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

interface ManagerInterface
{
    public function getControllers(): array;

    public function getActions(string $controller): array;

    public function getPath(string $controller, string $action): string;
}