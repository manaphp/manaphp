<?php
declare(strict_types=1);

namespace ManaPHP\Http\Response;

use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;

interface AppenderInterface
{
    public function append(RequestInterface $request, ResponseInterface $response): void;
}