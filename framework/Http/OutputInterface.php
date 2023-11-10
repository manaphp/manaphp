<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface OutputInterface
{
    public function json(mixed $data, int $status = 200, array $headers = []): ResponseInterface;

    public function fail(string $message = '', mixed $data = null): ResponseInterface;

    public function success(string $message = '', mixed $data = null): ResponseInterface;
}