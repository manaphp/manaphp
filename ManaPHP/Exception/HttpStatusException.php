<?php
namespace ManaPHP\Exception;

use ManaPHP\Exception;

class HttpStatusException extends Exception
{
    /**
     * @var int
     */
    protected $_statusCode;

    /**
     * @var string
     */
    protected $_statusText;

    public function __construct($statusCode, $statusText = '', $previous = null)
    {
        static $codeTexts = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested range unsatisfiable',
            417 => 'Expectation failed',
            418 => 'I\'m a teapot',
            421 => 'Misdirected Request',
            422 => 'Unprocessable entity',
            423 => 'Locked',
            424 => 'Method failure',
            425 => 'Unordered Collection',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            449 => 'Retry With',
            450 => 'Blocked by Windows Parental Controls',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway or Proxy Error',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out',
            505 => 'HTTP Version not supported',
            507 => 'Insufficient storage',
            508 => 'Loop Detected',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
        ];

        $this->_statusCode = $statusCode;

        if (!$statusText) {
            $statusText = isset($codeTexts[$statusCode]) ? $codeTexts[$statusCode] : 'App Error';
        }

        $this->_statusText = $statusText;

        parent::__construct($statusText, $statusCode, $previous);
    }

    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    public function getStatusText()
    {
        return $this->_statusText;
    }
}