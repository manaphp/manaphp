<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client;

class Exception extends \ManaPHP\Exception
{
    protected ?Response $response = null;

    public function __construct(string|array $message = '', ?Response $response = null, ?\Exception $previous = null)
    {
        $this->response = $response;
        parent::__construct($message, 0, $previous);
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }
}