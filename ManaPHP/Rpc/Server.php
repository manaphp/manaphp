<?php

namespace ManaPHP\Rpc;

use ManaPHP\Aop\Unaspectable;
use ManaPHP\Component;
use Throwable;

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
abstract class Server extends Component implements ServerInterface, Unaspectable
{
    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = 9501;

    /**
     * @var \ManaPHP\Rpc\Server\HandlerInterface
     */
    protected $_handler;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['host'])) {
            $this->_host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
        }
    }

    /**
     * @return bool
     */
    public function authenticate()
    {
        if ($this->_handler->authenticate() !== false) {
            return true;
        }

        if (!$this->response->getContent()) {
            $this->response->setStatus(401)->setJsonError('Unauthorized', 401);
        }

        return false;
    }
}