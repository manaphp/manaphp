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
    public $stats;

    /**
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];
        foreach ($this->headers as $i => $header) {
            if (($pos = strpos($header, ': ')) === false) {
                continue;
            }

            $name = substr($header, 0, $pos);
            $value = substr($header, $pos + 2);
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

    /**
     * @return string
     */
    public function getUtf8Body()
    {
        $body = $this->body;
        if (preg_match('#charset=([\w-]+)#i', $this->content_type, $match) === 1) {
            $charset = strtoupper($match[1]);
            if ($charset !== 'UTF-8' && $charset !== 'UTF8') {
                $body = iconv($charset, 'UTF-8', $body);
            }
        }

        return $body;
    }
}