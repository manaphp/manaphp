<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Http\ResponseInterface;

class ResponseStringify
{
    public function __construct(public ResponseInterface $response)
    {

    }
}