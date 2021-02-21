<?php

namespace ManaPHP\Http;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\ResponseInterface        $response
 * @property-read \ManaPHP\Http\Globals\ManagerInterface $globalsManager
 */
abstract class Server extends Component implements ServerInterface
{
    /**
     * @var bool
     */
    protected $use_globals = false;

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
        if (isset($options['use_globals'])) {
            $this->use_globals = (bool)$options['use_globals'];
        }

        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->port = (int)$options['port'];
        }
    }
}