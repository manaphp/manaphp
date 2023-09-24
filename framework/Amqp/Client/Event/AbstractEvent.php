<?php
declare(strict_types=1);

namespace ManaPHP\Amqp\Client\Event;

use JsonSerializable;
use Stringable;

class AbstractEvent implements JsonSerializable, Stringable
{
    public function jsonSerialize(): array
    {
        $vars = get_object_vars($this);
        unset($vars['client']);

        return $vars;
    }

    public function __toString(): string
    {
        return json_stringify($this->jsonSerialize());
    }
}