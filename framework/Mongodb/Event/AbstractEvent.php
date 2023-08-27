<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Event;

use JsonSerializable;
use Stringable;

class AbstractEvent implements JsonSerializable, Stringable
{
    public function jsonSerialize(): mixed
    {
        $vars = get_object_vars($this);
        unset($vars['mongodb']);

        return $vars;
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}