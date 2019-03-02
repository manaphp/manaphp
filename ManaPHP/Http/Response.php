<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Http\Filter\Exception as FilterException;

/**
 * Class ManaPHP\Http\Response
 *
 * @package response
 *
 * @property-read \ManaPHP\Http\CookiesInterface $cookies
 * @property-read \ManaPHP\UrlInterface          $url
 * @property-read \ManaPHP\RouterInterface       $router
 */
class Response extends Component implements ResponseInterface
{
    /**
     * @var string
     */
    protected $_content;

    /**
     * @var array
     */
    protected $_headers = [];

    /**
     * @var string
     */
    protected $_status;

    /**
     * @var string
     */
    protected $_file;

    /**
     * @return array|bool
     */
    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->_content = null;
        $this->_headers = [];
        $this->_status = null;
        $this->_file = null;
    }

    /**
     * Sets the HTTP response code
     *<code>
     *    $response->setStatusCode(404, "Not Found");
     *</code>
     *
     * @param int    $code
     * @param string $text
     *
     * @return static
     */
    public function setStatus($code, $text = null)
    {
        $this->_status = $code . ' ' . ($text ?: $this->getStatusText($code));

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->_status ? (int)substr($this->_status, 0, strpos($this->_status, ' ')) : 200;
    }

    /**
     * @param int $code
     *
     * @return string
     */
    public function getStatusText($code = null)
    {
        if ($code === null) {
            return $this->_status === null ? 'OK' : substr($this->_status, strpos($this->_status, ' ') + 1);
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

            return isset($texts[$code]) ? $texts[$code] : 'App Error';
        }
    }

    /**
     * Overwrites a header in the response
     *<code>
     *    $response->setHeader("Content-Type", "text/plain");
     *</code>
     *
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    public function setHeader($name, $value)
    {
        $this->_headers[$name] = $value;

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
        return isset($this->_headers[$name]) ? $this->_headers[$name] : $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader($name)
    {
        return isset($this->_headers[$name]);
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public function removeHeader($name)
    {
        unset($this->_headers[$name]);

        return $this;
    }

    /**
     * Sets a Expires header to use HTTP cache
     *<code>
     *    $this->response->setExpires(new DateTime());
     *</code>
     *
     * @param int $timestamp
     *
     * @return static
     */
    public function setExpires($timestamp)
    {
        if ($timestamp <= 2592000) {
            $timestamp += time();
        }

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $date->setTimestamp($timestamp);

        $this->setHeader('Expires', $date->format('D, d M Y H:i:s') . ' GMT');

        return $this;
    }

    /**
     * Sets a Not-Modified response
     *
     * @return static
     */
    public function setNotModified()
    {
        $this->setStatus(304, 'Not modified');

        return $this;
    }

    /**
     * Set a custom ETag
     *<code>
     *    $response->setEtag(md5(time()));
     *</code>
     *
     * @param string $etag
     *
     * @return static
     */
    public function setEtag($etag)
    {
        $this->setHeader('Etag', $etag);

        return $this;
    }

    /**
     * @param string $control
     *
     * @return static
     */
    public function setCacheControl($control)
    {
        return $this->setHeader('Cache-Control', $control);
    }

    /**
     * @param int    $age
     * @param string $extra
     *
     * @return static
     */
    public function setMaxAge($age, $extra = null)
    {
        $this->setHeader('Cache-Control', $extra ? "$extra, max-age=$age" : "max-age=$age");
        $this->setExpires(time() + $age);

        return $this;
    }

    /**
     * Sets the response content-type mime, optionally the charset
     *<code>
     *    $response->setContentType('application/pdf');
     *    $response->setContentType('text/plain', 'UTF-8');
     *</code>
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
        return isset($this->_headers['Content-Type']) ? $this->_headers['Content-Type'] : null;
    }

    /**
     * Redirect by HTTP to another action or URL
     *<code>
     *  //Using a string redirect (internal/external)
     *    $response->redirect("posts/index");
     *    $response->redirect("http://www.google.com");
     *    $response->redirect("http://www.example.com/new-location", false);
     *</code>
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

        if (isset($this->url)) {
            $this->setHeader('Location', is_array($location) ? call_user_func_array([$this->url, 'get'], $location) : $this->url->get($location));
        } else {
            $this->setHeader('Location', $location);
        }

        return $this;
    }

    /**
     * Redirect by HTTP to another action or URL
     *
     * @param string|array $action
     * @param bool         $temporarily
     *
     * @return static
     */
    public function redirectToAction($action, $temporarily = true)
    {
        if ($temporarily) {
            $this->setStatus(302, 'Temporarily Moved');
        } else {
            $this->setStatus(301, 'Permanently Moved');
        }

        $this->setHeader('Location', $this->router->createUrl($action, true));

        return $this;
    }

    /**
     * Sets HTTP response body
     *<code>
     *    $response->setContent("<h1>Hello!</h1>");
     *</code>
     *
     * @param string $content
     *
     * @return static
     */
    public function setContent($content)
    {
        $this->_content = (string)$content;

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
     * @param string|array $data
     *
     * @return string
     */
    protected function _jsonEncode($data)
    {
        return is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }

    /**
     * Sets HTTP response body. The parameter is automatically converted to JSON
     *<code>
     *    $response->setJsonContent(array("status" => "OK"));
     *    $response->setJsonContent(array("status" => "OK"));
     *</code>
     *
     * @param array|\JsonSerializable|int|string|\Exception $content
     *
     * @return static
     */
    public function setJsonContent($content)
    {
        $this->setHeader('Content-Type', 'application/json; charset=utf-8');

        if (is_array($content)) {
            if (!isset($content['code'])) {
                $content = ['code' => 0, 'message' => '', 'data' => $content];
            }
        } elseif ($content instanceof \JsonSerializable) {
            $content = ['code' => 0, 'message' => '', 'data' => $content];
        } elseif (is_string($content)) {
            null;
        } elseif (is_int($content)) {
            $content = ['code' => $content, 'message' => ''];
        } elseif ($content === null) {
            $content = ['code' => 0, 'message' => '', 'data' => null];
        } elseif ($content instanceof FilterException) {
            $content = ['code' => -2, 'message' => $content->getMessage()];
        } elseif ($content instanceof \Exception) {
            if ($content instanceof \ManaPHP\Exception) {
                $this->setStatus($content->getStatusCode());
                $content = $content->getJson();
            } else {
                $this->setStatus(500);
                $content = ['code' => 500, 'message' => 'Server Internal Error'];
            }
        }

        $this->_content = $this->_jsonEncode($content);

        return $this;
    }

    /**
     * @param array $content
     *
     * @return static
     */
    public function setXmlContent($content)
    {
        $this->setContentType('text/xml');

        $writer = new \XMLWriter();

        $writer->openMemory();
        $writer->startDocument();
        $this->_toXml($writer, (count($content) !== 1) ? ['xml' => $content] : $content);
        $this->_content = $writer->outputMemory();

        return $this;
    }

    /**
     * @param \XMLWriter $writer
     * @param            $data
     */
    protected function _toXml($writer, $data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $writer->startElement($k);
                    $this->_toXml($writer, $v);
                    $writer->endElement();
                } else {
                    $writer->writeElement($k, $v);
                }
            }
        }
    }

    /**
     * Appends a string to the HTTP response body
     *
     * @param string $content
     *
     * @return static
     */
    public function appendContent($content)
    {
        $this->_content .= $content;

        return $this;
    }

    /**
     * Gets the HTTP response body
     *
     * @return string
     */
    public function getContent()
    {
        return $this->_content;
    }

    /**
     * Prints out HTTP response to the client
     *
     * @return static
     */
    public function send()
    {
        if (headers_sent($file, $line)) {
            throw new MisuseException("Headers has been sent in $file:$line");
        }

        if (isset($_SERVER['HTTP_X_REQUEST_ID']) && !isset($this->_headers['X-Request-Id'])) {
            $this->_headers['X-Request-Id'] = $_SERVER['HTTP_X_REQUEST_ID'];
        }

        $this->_headers['X-Response-Time'] = sprintf('%.3f', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

        $this->fireEvent('response:beforeSend');

        if ($this->_status) {
            header('HTTP/1.1 ' . $this->_status);
        }

        foreach ($this->_headers as $header => $value) {
            if ($value !== null) {
                header($header . ': ' . $value, true);
            } else {
                header($header, true);
            }
        }

        $this->cookies->send();

        if ($this->_file) {
            readfile($this->alias->resolve($this->_file));
        } else {
            echo $this->_content;
        }

        $this->fireEvent('response:afterSend');

        return $this;
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
        if ($attachmentName === null) {
            $attachmentName = basename($file);
        }

        if (!$this->filesystem->fileExists($file)) {
            throw new FileNotFoundException(['Sent file is not exists: `:file`', 'file' => $file]);
        }
        $this->setHeader('Content-Length', $this->filesystem->fileSize($file));

        $this->_file = $file;

        $this->setAttachment($attachmentName);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFile()
    {
        return $this->_file;
    }

    /**
     * @param string $attachmentName
     *
     * @return static
     */
    public function setAttachment($attachmentName)
    {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            if (strpos($userAgent, 'Trident') !== false || strpos($userAgent, 'MSIE') !== false) {
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
        return $this->_headers;
    }
}