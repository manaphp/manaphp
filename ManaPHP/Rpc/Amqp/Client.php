<?php

namespace ManaPHP\Rpc\Amqp;

use ManaPHP\Component;
use ManaPHP\Rpc\Amqp\Engine\Php;
use ManaPHP\Rpc\ClientInterface;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Client extends Component implements ClientInterface
{
    /**
     * @var string
     */
    protected $uri;

    /**
     * @var EngineInterface
     */
    protected $engine;

    /**
     * @var string
     */
    protected $exchange = 'rpc';

    /**
     * @var string
     */
    protected $routing_key = 'call';

    /**
     * @param string $uri
     */
    public function __construct($uri)
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

    /**
     * @param string       $exchange
     * @param string       $routing_key
     * @param string|array $body
     * @param array        $properties
     * @param array        $options
     *
     * @return mixed
     */
    public function call($exchange, $routing_key, $body, $properties = [], $options = [])
    {
        $engine = $this->engine;
        if (is_array($body)) {
            $body = json_stringify($body);
            if (!isset($properties['content_type'])) {
                $properties['content_type'] = 'application/json';
            }
        }

        return $engine->call($exchange, $routing_key, $body, $properties, $options);
    }

    /**
     * @param string $method
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function invoke($method, $params = [], $options = [])
    {
        $body = ['method' => $method, 'params' => $params];

        return $this->call($this->exchange, $this->routing_key, $body);
    }
}