<?php
namespace ManaPHP\Curl\Easy;

use ManaPHP\Exception\InvalidJsonException;

class Response
{
    /**
     * @var string
     */
    public $url;

    /**
     * @var string
     */
    public $remote_ip;

    /**
     * @var int
     */
    public $http_code;

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var float
     */
    public $process_time;

    /**
     * @var string
     */
    public $content_type;

    /**
     * @var string
     */
    public $body;

    /**
     * @var array
     */
    public $timeInfo;

    /**
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];
        foreach ($this->headers as $i => $header) {
            if ($i === 0) {
                continue;
            }

            list($name, $value) = explode(': ', $header, 2);
            if (isset($headers[$name])) {
                if (!is_array($headers[$name])) {
                    $headers[$name] = [$headers[$name], $value];
                } else {
                    $headers[$name][] = $value;
                }
            } else {
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    /**
     * @return array
     * @throws \ManaPHP\Exception\InvalidJsonException
     */
    public function getJsonBody()
    {
        $data = json_decode($this->body, true);
        if (!is_array($data)) {
            throw new InvalidJsonException(['response of `:url` url is not a valid json: `:response`',
                'url' => $this->url,
                'response' => substr($this->body, 0, 128)]);
        }

        return $data;
    }
}