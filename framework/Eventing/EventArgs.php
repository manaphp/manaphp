<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

class EventArgs
{
    public string $event;
    public ?object $source;
    public mixed $data;

    public function __construct(string $event, ?object $source, mixed $data)
    {
        $this->event = $event;
        $this->source = $source;
        $this->data = $data;
    }
}