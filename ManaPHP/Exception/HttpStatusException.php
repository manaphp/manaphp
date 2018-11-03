<?php
namespace ManaPHP\Exception;

use ManaPHP\Exception;

class HttpStatusException extends Exception
{
    /**
     * @var int
     */
    protected $_statusCode;

    public function __construct($statusCode, $message = '', $previous = null)
    {
        $this->_statusCode = $statusCode;
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode()
    {
        return $this->_statusCode;
    }
}