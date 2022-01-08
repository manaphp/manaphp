<?php

namespace ManaPHP\Amqp;

interface EngineInterface
{
    public function exchangeDeclare(Exchange $exchange): void;

    public function exchangeDelete(string $exchange, bool $if_unused = false, bool $nowait = false)：void;

    public function queueDeclare(Queue $queue): void;

    public function queueDelete(string $queue, bool $if_unused, bool $if_empty, bool $nowait): void;

    public function queueBind(Binding $binding): void;

    public function queueUnbind(Binding $binding): void;

    public function basicPublish(string|Exchange $exchange, string|Queue $routing_key, string|array $body,
        array $properties, bool $mandatory
    ): void;

    public function basicConsume(string|Queue $queue, callable $callback, bool $no_ack, bool $exclusive, string $tag
    ): string;

    public function wait(int $prefetchSize, int $prefetchCount): void;

    public function wrapMessage(mixed $message, string $queue): MessageInterface;
}