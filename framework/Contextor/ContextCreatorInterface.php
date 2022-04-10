<?php
declare(strict_types=1);

namespace ManaPHP\Contextor;

interface ContextCreatorInterface
{
    public function createContext(): mixed;
}