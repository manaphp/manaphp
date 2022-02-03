<?php
declare(strict_types=1);

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
}