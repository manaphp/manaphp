<?php

namespace ManaPHP\Rpc;

interface ClientInterface
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
     * @param string          $method
     * @param array           $params
     * @param array|int|float $options
     *
     * @return mixed
     */
    public function invoke($method, $params = [], $options = null);
}