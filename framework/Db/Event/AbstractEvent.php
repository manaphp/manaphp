<?php
declare(strict_types=1);

namespace ManaPHP\Db\Event;

use JsonSerializable;
use Stringable;

class AbstractEvent implements JsonSerializable, Stringable
{
    public function jsonSerialize(): mixed
    {
        $vars = get_object_vars($this);
        unset($vars['db'], $vars['pdo']);

        return $vars;
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}