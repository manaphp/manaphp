<?php
declare(strict_types=1);

namespace ManaPHP\Amqp;

interface MessageInterface
{
    public function getQueue(): string;

    public function getProperties(): array;

    public function getBody(): string;

    public function getJsonBody(): array;

    public function getExchange(): string;

    public function getRoutingKey(): string;

    public function getDeliveryTag(): int;

    public function isRedelivered(): bool;

    public function getReplyTo(): string;
}