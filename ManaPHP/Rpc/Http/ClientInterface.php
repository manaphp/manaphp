<?php

namespace ManaPHP\Rpc\Http;

interface ClientInterface extends \ManaPHP\Rpc\ClientInterface
{
    /**
     * @param string $endpoint
     *
     * @return static
     */
    public function setEndpoint($endpoint);

    /**
     * @return string
     */
    public function getEndpoint();

    /**
     * @param string $method
     * @param array  $params
     * @param array  $options
     *
     * @return mixed
     */
    public function invoke($method, $params = [], $options = []);
}