<?php

namespace ManaPHP\Http;

use DateTime;
use DateTimeZone;
use JsonSerializable;
use ManaPHP\Component;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\ConfigInterface       $config
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Http\UrlInterface     $url
 * @property-read \ManaPHP\Http\RouterInterface  $router
 * @property-read \ManaPHP\Http\ResponseContext  $context
 */
class Response extends Component implements ResponseInterface
{
    /**
     * @var int|string
     */
    protected $ok_code = 0;

    /**
     * @var int|string
     */
    protected $error_code = 1;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['ok_code'])) {
            $this->ok_code = $options['ok_code'];
        }

        if (isset($options['error_code'])) {
            $this->error_code = $options['error_code'];
        }
    }

    /**
     * @param string $name
     * @param mixed  $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     *
     * @return static
     */
    public function setCookie(
        $name,
        $value,
        $expire = 0,
        $path = null,
        $domain = null,
        $secure = false,
        $httponly = true
    ) {
        $context = $this->context;

        if ($expire > 0) {
            $current = time();
            if ($expire < $current) {
                $expire += $current;
            }
        }

        $context->cookies[$name] = [
            'name'     => $name,
            'value'    => $value,
            'expire'   => $expire,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httponly
        ];

        return $this;
    }

    /**
     * @return array
     */
    public function getCookies()
    {
        return $this->context->cookies;
    }

    /**
     * Sets the HTTP response code
     *
     * @param int    $code
     * @param string $text
     *
     * @return static
     */
    public function setStatus($code, $text = null)
    {
        $context = $this->context;

        $context->status_code = (int)$code;
        $context->status_text = $text ?: $this->getStatusText($code);

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $context = $this->context;

        return $context->status_code . ' ' . $context->status_text;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->context->status_code;
    }

    /**
     * @param int $code
     *
     * @return string
     */
    public function getStatusText($code = null)
    {
        if ($code === null) {
            return $this->context->status_text;
        } else {
            $texts = [
                200 => 'OK',
                201 => 'Created',
                202 => 'Accepted',
                203 => 'Non-Authoritative Information',
                204 => 'No Content',
                205 => 'Reset Content',
                206 => 'Partial Content',
                207 => 'Multi-Status',
                208 => 'Already Reported',
                301 => 'Moved Permanently',
                302 => 'Found',
                303 => 'See Other',
                304 => 'Not Modified',
                305 => 'Use Proxy',
                307 => 'Temporary Redirect',
                308 => 'Permanent Redirect',
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

            return $texts[$code] ?? 'App Error';
        }
    }

    /**
     * Overwrites a header in the response
     *
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    public function setHeader($name, $value)
    {
        $context = $this->context;

        $context->headers[$name] = $value;

        return $this;
    }

    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    public function getHeader($name, $default = null)
    {
        $context = $this->context;

        return $context->headers[$name] ?? $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader($name)
    {
        $context = $this->context;

        return isset($context->headers[$name]);
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function removeHeader($name)
    {
        $context = $this->context;

        unset($context->headers[$name]);

        return $this;
    }

    /**
     * Sets a Expires header to use HTTP cache
     *
     * @param int $timestamp
     *
     * @return static
     */
    public function setExpires($timestamp)
    {
        if (str_contains('GET,OPTIONS', $this->request->getMethod())) {
            if ($timestamp <= 2592000) {
                $timestamp += time();
            }

            $date = new DateTime('now', new DateTimeZone('UTC'));
            $date->setTimestamp($timestamp);

            $this->setHeader('Expires', $date->format('D, d M Y H:i:s') . ' GMT');
        }

        return $this;
    }

    /**
     * Sets a Not-Modified response
     *
     * @return static
     */
    public function setNotModified()
    {
        $context = $this->context;

        $context->status_code = 304;
        $context->status_text = 'Not Modified';

        return $this;
    }

    /**
     * Set a custom ETag
     *
     * @param string $etag
     *
     * @return static
     */
    public function setETag($etag)
    {
        $this->setHeader('ETag', $etag);

        return $this;
    }

    /**
     * @param string $control
     *
     * @return static
     */
    public function setCacheControl($control)
    {
        if (str_contains('GET,OPTIONS', $this->request->getMethod())) {
            return $this->setHeader('Cache-Control', $control);
        }

        return $this;
    }

    /**
     * @param int    $age
     * @param string $extra
     *
     * @return static
     */
    public function setMaxAge($age, $extra = null)
    {
        if (str_contains('GET,OPTIONS', $this->request->getMethod())) {
            $this->setHeader('Cache-Control', $extra ? "$extra, max-age=$age" : "max-age=$age");
            $this->setExpires(time() + $age);
        }

        return $this;
    }

    /**
     * Sets the response content-type mime, optionally the charset
     *
     * @param string $contentType
     * @param string $charset
     *
     * @return static
     */
    public function setContentType($contentType, $charset = null)
    {
        if ($charset === null) {
            $this->setHeader('Content-Type', $contentType);
        } else {
            $this->setHeader('Content-Type', $contentType . '; charset=' . $charset);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        $context = $this->context;

        return $context->headers['Content-Type'] ?? null;
    }

    /**
     * Redirect by HTTP to another action or URL
     *
     * @param string|array $location
     * @param bool         $temporarily
     *
     * @return static
     */
    public function redirect($location, $temporarily = true)
    {
        if ($temporarily) {
            $this->setStatus(302, 'Temporarily Moved');
        } else {
            $this->setStatus(301, 'Permanently Moved');
        }

        $this->setHeader('Location', $this->url->get($location));

        throw new AbortException();

        /** @noinspection PhpUnreachableStatementInspection */
        return $this;
    }

    /**
     * Sets HTTP response body
     *
     * @param mixed $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $context = $this->context;

        $context->content = $content;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return static
     */
    public function setJsonOk($message = '')
    {
        return $this->setJsonContent(['code' => $this->ok_code, 'message' => $message]);
    }

    /**
     * @param string $message
     * @param int    $code
     *
     * @return static
     */
    public function setJsonError($message, $code = null)
    {
        return $this->setJsonContent(['code' => $code ?? $this->error_code, 'message' => $message]);
    }

    /**
     * @param mixed  $data
     * @param string $message
     *
     * @return static
     */
    public function setJsonData($data, $message = '')
    {
        return $this->setJsonContent(['code' => $this->ok_code, 'message' => $message, 'data' => $data]);
    }

    /**
     * @param \Throwable $throwable
     *
     * @return static
     */
    public function setJsonThrowable($throwable)
    {
        if ($throwable instanceof \ManaPHP\Exception) {
            $code = $throwable->getStatusCode();
            $json = $throwable->getJson();
        } else {
            $code = 500;
            $json = ['code' => $code, 'message' => 'Internal Server Error'];
        }

        if ($this->config->get('debug')) {
            $json['message'] = get_class($throwable) . ": " . $throwable->getMessage();
            $json['exception'] = explode("\n", $throwable);
        }

        return $this->setStatus($code)->setJsonContent($json);
    }

    /**
     * Sets HTTP response body. The parameter is automatically converted to JSON
     *
     * @param array|\JsonSerializable|string $content
     *
     * @return static
     */
    public function setJsonContent($content)
    {
        $context = $this->context;

        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        if (is_array($content) || is_string($content)) {
            null;
        } elseif ($content instanceof JsonSerializable) {
            $content = ['code' => $this->ok_code, 'message' => '', 'data' => $content];
        }

        $context->content = $content;

        return $this;
    }

    /**
     * Gets the HTTP response body
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->context->content;
    }

    /**
     * @return bool
     */
    public function hasContent()
    {
        $content = $this->context->content;

        return $content !== '' && $content !== null;
    }

    /**
     * Sets an attached file to be sent at the end of the request
     *
     * @param string $file
     * @param string $attachmentName
     *
     * @return static
     */
    public function setFile($file, $attachmentName = null)
    {
        $context = $this->context;

        if ($attachmentName === null) {
            $attachmentName = basename($file);
        }

        if (!LocalFS::fileExists($file)) {
            throw new FileNotFoundException(['Sent file is not exists: `:file`', 'file' => $file]);
        }
        $this->setHeader('Content-Length', LocalFS::fileSize($file));

        $context->file = $file;

        $this->setAttachment($attachmentName);

        return $this;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->context->file;
    }

    /**
     * @return bool
     */
    public function hasFile()
    {
        return (bool)$this->context->file;
    }

    /**
     * @param string $attachmentName
     *
     * @return static
     */
    public function setAttachment($attachmentName)
    {
        if ($userAgent = $this->request->getUserAgent()) {
            if (str_contains($userAgent, 'Trident') || str_contains($userAgent, 'MSIE')) {
                $attachmentName = urlencode($attachmentName);
            }
        }

        $this->setHeader('Content-Description', 'File Transfer');
        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'attachment; filename=' . $attachmentName);
        $this->setHeader('Content-Transfer-Encoding', 'binary');
        $this->setHeader('Cache-Control', 'must-revalidate');

        return $this;
    }

    /**
     * @param array        $rows
     * @param string       $name
     * @param array|string $header
     *
     * @return static
     */
    public function setCsvContent($rows, $name, $header = null)
    {
        $this->setAttachment(pathinfo($name, PATHINFO_EXTENSION) === 'csv' ? $name : $name . '.csv');

        $file = fopen('php://temp', 'rb+');
        fprintf($file, "\xEF\xBB\xBF");

        if (is_string($header)) {
            $header = explode(',', $header);
        } elseif ($header === null && $first = current($rows)) {
            $header = array_keys(is_array($first) ? $first : $first->toArray());
        }

        if ($header !== null) {
            fputcsv($file, $header);
        }

        foreach ($rows as $row) {
            fputcsv($file, is_array($row) ? $row : $row->toArray());
        }

        rewind($file);
        $content = stream_get_contents($file);
        fclose($file);

        $this->setContentType('text/csv');
        $this->setContent($content);

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->context->headers;
    }

    public function dump(): array
    {
        $data = parent::dump();

        $data['context']['content'] = '***';
        if (isset($data['context']['headers']['X-Logger'])) {
            $data['context']['headers']['X-Logger'] = '***';
        }

        return $data;
    }
}