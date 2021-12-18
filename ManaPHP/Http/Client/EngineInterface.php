<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

interface EngineInterface
{
    public function request(Request $request, string $body): Response;
}