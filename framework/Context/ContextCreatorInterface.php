<?php
declare(strict_types=1);

namespace ManaPHP\Context;

interface ContextCreatorInterface
{
    public function createContext(): mixed;
}