<?php

namespace ManaPHP\Rpc;

use ManaPHP\Aop\Unaspectable;
use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
abstract class Server extends Component implements ServerInterface, Unaspectable
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
     * @var \ManaPHP\Rpc\Server\HandlerInterface
     */
    protected $handler;

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

    /**
     * @return bool
     */
    public function authenticate()
    {
        if ($this->handler->authenticate() !== false) {
            return true;
        }

        if (!$this->response->getContent()) {
            $this->response->setStatus(401)->setJsonError('Unauthorized', 401);
        }

        return false;
    }
}