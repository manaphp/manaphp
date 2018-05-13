<?php
namespace ManaPHP\Curl\Easy;

class Exception extends \ManaPHP\Exception
{
    /**
     * @var \ManaPHP\Curl\Easy\Response
     */
    protected $_response;

    /**
     * Exception constructor.
     *
     * @param string|array                $message
     * @param \ManaPHP\Curl\Easy\Response $response
     * @param \Exception|null             $previous
     */
    public function __construct($message = '', $response = null, \Exception $previous = null)
    {
        $this->_response = $response;
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param \ManaPHP\Curl\Easy\Response $response
     */
    public function setResponse($response)
    {
        $this->_response = $response;
    }

    /**
     * @return \ManaPHP\Curl\Easy\Response
     */
    public function getResponse()
    {
        return $this->_response;
    }
}