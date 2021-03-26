<?php

namespace ManaPHP\Rpc\Http;

use ManaPHP\Component;

abstract class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $endpoint;

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }
}