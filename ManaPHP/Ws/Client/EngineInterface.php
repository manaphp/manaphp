<?php

namespace ManaPHP\Ws\Client;

interface EngineInterface
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
     * @param int    $op_code
     * @param string $data
     * @param float  $timeout
     *
     * @return mixed
     */
    public function send($op_code, $data, $timeout);

    /**
     * @param float $timeout
     *
     * @return bool
     */
    public function isRecvReady($timeout);

    /**
     * @param float $timeout
     *
     * @return \ManaPHP\Ws\Client\Message
     */
    public function recv($timeout = null);


    public function close();
}