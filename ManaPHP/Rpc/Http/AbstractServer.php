<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Http;

use ManaPHP\Component;
use ManaPHP\Rpc\ServerInterface;

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Rpc\HandlerInterface   $rpcHandler
 * @property-read \ManaPHP\Http\GlobalsInterface  $globals
 */
abstract class AbstractServer extends Component implements ServerInterface
{
    protected string $host = '0.0.0.0';
    protected int $port = 9501;

    public function __construct(array $options = [])
    {
        if (isset($options['host'])) {
            $this->host = $options['host'];
        }

        if (isset($options['port'])) {
            $this->port = (int)$options['port'];
        }
    }

    public function authenticate(): bool
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