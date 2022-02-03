<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

class Binding
{
    public string|Queue $queue;
    public string|Exchange $exchange;
    public string $binding_key;
    public array $arguments;

    public function __construct(string|Queue $queue, string|Exchange $exchange, string $binding_key,
        array $arguments = []
    ) {
        $this->queue = $queue;
        $this->exchange = $exchange;
        $this->binding_key = $binding_key;
        $this->arguments = $arguments;
    }
}