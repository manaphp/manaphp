<?php
declare(strict_types=1);

namespace ManaPHP\Rpc\Amqp;

use ManaPHP\Component;
use ManaPHP\Rpc\Amqp\Engine\Php;
use ManaPHP\Rpc\ClientInterface;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Client extends Component implements ClientInterface
{
    protected string $uri;
    protected EngineInterface $engine;
    protected string $exchange = 'rpc';
    protected string $routing_key = 'call';

    public function __construct(string $uri)
    {
        $this->uri = $uri;

        $this->engine = new Php($uri);

        if (preg_match('#\bexhange=(\w+)\b#', $uri, $match)) {
            $this->exchange = $match[1];
        }

        if (preg_match('#\brouting_key=(\w+)\b#', $uri, $match)) {
            $this->routing_key = $match[1];
        }
    }

    public function call(string $exchange, string $routing_key, string|array $body, array $properties = [],
        array $options = []
    ): mixed {
        $engine = $this->engine;
        if (is_array($body)) {
            $body = json_stringify($body);
            if (!isset($properties['content_type'])) {
                $properties['content_type'] = 'application/json';
            }
        }

        return $engine->call($exchange, $routing_key, $body, $properties, $options);
    }

    public function invoke(string $method, array $params = [], array $options = []): mixed
    {
        $body = ['method' => $method, 'params' => $params];

        return $this->call($this->exchange, $this->routing_key, $body);
    }
}