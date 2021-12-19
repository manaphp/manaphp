<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Http;

use ManaPHP\Component;

abstract class AbstractClient extends Component implements ClientInterface
{
    protected string $endpoint;

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}