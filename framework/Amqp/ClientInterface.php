<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

interface ClientInterface
{
    public function exchangeDeclare(Exchange $exchange): void;

    public function exchangeDelete(string $exchange, bool $if_unused = false, bool $nowait = false): void;

    public function queueDeclare(Queue $queue): void;

    public function queueDelete(string $queue, bool $if_unused = false, bool $if_empty = false, bool $nowait = false
    ): void;

    public function queueBind(Binding $binding): void;

    public function queueUnbind(Binding $binding): void;

    public function basicPublish(string|Exchange $exchange, string|Queue $routing_key, string|array $body,
        array $properties = [], bool $mandatory = false
    ): void;

    public function basicConsume(string|Queue $queue, callable $callback, bool $no_ack = false, bool $exclusive = false,
        string $tag = ''
    ): string;

    public function startConsume(int $prefetch_size = 0, int $prefetch_count = 0): void;
}
