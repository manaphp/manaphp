<?php
declare(strict_types=1);

namespace ManaPHP\Di\Event;

use JsonSerializable;
use ManaPHP\Eventing\Attribute\Verbosity;
use function get_class;

#[Verbosity(Verbosity::HIGH)]
class SingletonCreated implements JsonSerializable
{
    public function __construct(public string $id, public object $instance, public array $definitions)
    {

    }

    public function jsonSerialize(): array
    {
        return [
            'id'       => $this->id,
            'instance' => get_class($this->instance),
        ];
    }
}