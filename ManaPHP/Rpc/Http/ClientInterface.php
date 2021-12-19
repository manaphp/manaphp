<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Http;

interface ClientInterface extends \ManaPHP\Rpc\ClientInterface
{
    public function setEndpoint(string $endpoint): static;

    public function getEndpoint(): string;

    public function invoke(string $method, array $params = [], array $options = []): mixed;
}