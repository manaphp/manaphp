<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Http\Client\Response;

interface ClientInterface
{
    public function rest(string $method, string|array $url, string|array $body = [], array $headers = [],
        mixed $options = []
    ): Response;

    public function request(string $method, string|array $url, null|string|array $body = null, array $headers = [],
        array $options = []
    ): Response;

    public function get(string|array $url, array $headers = [], mixed $options = []): Response;

    public function post(string|array $url, string|array $body = [], array $headers = [], mixed $options = []
    ): Response;

    public function delete(string|array $url, array $headers = [], mixed $options = []): Response;

    public function put(string|array $url, string|array $body = [], array $headers = [], mixed $options = []): Response;

    public function patch(string|array $url, string|array $body = [], array $headers = [], mixed $options = []
    ): Response;

    public function head(string|array $url, string|array $body = [], array $headers = [], mixed $options = []
    ): Response;
}