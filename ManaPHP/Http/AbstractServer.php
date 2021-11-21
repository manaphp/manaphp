<?php

namespace ManaPHP\Http;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Http\HandlerInterface  $httpHandler
 * @property-read \ManaPHP\Http\GlobalsInterface  $globals
 */
abstract class AbstractServer extends Component implements ServerInterface
{
    /**
     * @var string
     */
    protected $host = '0.0.0.0';

    /**
     * @var int
     */
    protected $port = 9501;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->port = (int)$options['port'];
        }
    }
}