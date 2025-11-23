<?php
declare(strict_types=1);

namespace ManaPHP\Alias;

class InvalidAliasNameException extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct('The alias name "{name}" must start with "@" character.', ['name' => $name]);
    }
}