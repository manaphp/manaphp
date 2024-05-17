<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

class Those implements ThoseInterface
{
    protected array $those = [];

    public function get(string $class): Entity
    {
        if (($that = $this->those[$class] ?? null) !== null) {
            return $that;
        } else {
            return $this->those[$class] = new $class();
        }
    }
}