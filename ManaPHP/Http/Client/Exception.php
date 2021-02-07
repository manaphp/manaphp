<?php

namespace ManaPHP\Http\Client;

class Exception extends \ManaPHP\Exception
{
    /**
     * @var \ManaPHP\Http\Client\Response
     */
    protected $response;

    /**
     * @param string|array                  $message
     * @param \ManaPHP\Http\Client\Response $response
     * @param \Exception|null               $previous
     */
    public function __construct($message = '', $response = null, \Exception $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param \ManaPHP\Http\Client\Response $response
     *
     * @return void
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return \ManaPHP\Http\Client\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}