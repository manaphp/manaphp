<?php

namespace ManaPHP\Rpc\Http;

use ManaPHP\Component;
use ManaPHP\Rpc\ServerInterface;

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Rpc\HandlerInterface   $rpcHandler
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

    /**
     * @return bool
     */
    public function authenticate()
    {
        if ($this->rpcHandler->authenticate() !== false) {
            return true;
        }

        if (!$this->response->getContent()) {
            $this->response->setStatus(401)->setJsonError('Unauthorized', 401);
        }

        return false;
    }
}