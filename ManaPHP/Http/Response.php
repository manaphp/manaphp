<?php

namespace ManaPHP\Http;

use DateTime;
use DateTimeZone;
use JsonSerializable;
use ManaPHP\Component;
use ManaPHP\Exception\AbortException;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Helper\LocalFS;
use Throwable;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class ResponseContext
{
    /**
     * @var int
     */
    public $status_code = 200;

    /**
     * @var string
     */
    public $status_text = 'OK';

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @var array
     */
    public $cookies = [];

    /**
     * @var mixed
     */
    public $content = '';

    /**
     * @var string
     */
    public $file;
}

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Http\UrlInterface     $url
 * @property-read \ManaPHP\Http\RouterInterface  $router
 * @property-read \ManaPHP\Http\ResponseContext  $_context
 */
class Response extends Component implements ResponseInterface
{
    /**
     * @return \ManaPHP\Http\ResponseContext
     */
    public function getContext()
    {
        return $this->_context;
    }

    /**
     * Sets a cookie to be sent at the end of the request
     *
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
        $context = $this->_context;

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

        $globals = $this->request->getContext();

        $globals->_COOKIE[$name] = $value;

        return $this;
    }

    /**
     * Deletes a cookie by its name
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     *
     * @return static
     */
    public function deleteCookie($name, $path = null, $domain = null, $secure = false, $httponly = true)
    {
        $context = $this->_context;

        $context->cookies[$name] = [
            'name'     => $name,
            'value'    => 'deleted',
            'expire'   => 1,
            'path'     => $path,
            'domain'   => $domain,
            'secure'   => $secure,
            'httponly' => $httponly
        ];

        $globals = $this->request->getContext();

        unset($globals->_COOKIE[$name]);

        return $this;
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
        $context = $this->_context;

        $context->status_code = (int)$code;
        $context->status_text = $text ?: $this->getStatusText($code);

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        $context = $this->_context;

        return $context->status_code . ' ' . $context->status_text;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->_context->status_code;
    }

    /**
     * @param int $code
     *
     * @return string
     */
    public function getStatusText($code = null)
    {
        if ($code === null) {
            return $this->_context->status_text;
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
        $context = $this->_context;

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
        $context = $this->_context;

        return $context->headers[$name] ?? $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader($name)
    {
        $context = $this->_context;

        return isset($context->headers[$name]);
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function removeHeader($name)
    {
        $context = $this->_context;

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
        $context = $this->_context;

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
        $context = $this->_context;

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
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $context = $this->_context;

        $context->content = (string)$content;

        return $this;
    }

    /**
     * @param string $message
     *
     * @return static
     */
    public function setJsonOk($message = '')
    {
        return $this->setJsonContent(['code' => 0, 'message' => $message]);
    }

    /**
     * @param string $message
     * @param int    $code
     *
     * @return static
     */
    public function setJsonError($message, $code = 1)
    {
        return $this->setJsonContent(['code' => $code, 'message' => $message]);
    }

    /**
     * @param mixed  $data
     * @param string $message
     *
     * @return static
     */
    public function setJsonData($data, $message = '')
    {
        return $this->setJsonContent(['code' => 0, 'message' => $message, 'data' => $data]);
    }

    /**
     * Sets HTTP response body. The parameter is automatically converted to JSON
     *
     * @param array|\JsonSerializable|string|\Exception $content
     *
     * @return static
     */
    public function setJsonContent($content)
    {
        $context = $this->_context;

        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        if (is_array($content) || is_string($content)) {
            null;
        } elseif ($content instanceof JsonSerializable) {
            $content = ['code' => 0, 'message' => '', 'data' => $content];
        } elseif ($content instanceof \ManaPHP\Exception) {
            $this->setStatus($content->getStatusCode());
            $content = $content->getJson();
        } elseif ($content instanceof Throwable) {
            $this->setStatus(500);
            $content = ['code' => 500, 'message' => 'Server Internal Error'];
        }

        $context->content = $content;

        return $this;
    }

    /**
     * Gets the HTTP response body
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_context->content;
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
        $context = $this->_context;

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
     * @return string|null
     */
    public function getFile()
    {
        return $this->_context->file;
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
        return $this->_context->headers;
    }

    public function dump()
    {
        $data = parent::dump();

        $data['_context']['content'] = '***';
        if (isset($data['_context']['headers']['X-Logger'])) {
            $data['_context']['headers']['X-Logger'] = '***';
        }

        return $data;
    }
}