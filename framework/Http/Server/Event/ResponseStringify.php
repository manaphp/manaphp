<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Event;

use ManaPHP\Eventing\Attribute\Verbosity;
use ManaPHP\Http\ResponseInterface;

#[Verbosity(Verbosity::HIGH)]
class ResponseStringify
{
    public function __construct(public ResponseInterface $response)
    {

    }
}