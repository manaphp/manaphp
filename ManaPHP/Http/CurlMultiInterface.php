<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Http\CurlMulti\Request;
use ManaPHP\Http\CurlMulti\Error;
use ManaPHP\Http\CurlMulti\Response;

interface CurlMultiInterface
{
    public function add(string|array|Request $request, ?callable $callbacks = null): static;

    public function download(string|array $url, string $target, ?callable $callback = null): static;

    public function start(): static;

    public function onSuccess(Response $response): void;

    public function onError(Error $error): void;
}