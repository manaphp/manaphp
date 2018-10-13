<?php

namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Filter\Exception as FilterException;
use ManaPHP\Http\Response\Exception as ResponseException;

/**
 * Class ManaPHP\Http\Response
 *
 * @package response
 *
 * @property \ManaPHP\Http\CookiesInterface $cookies
 * @property \ManaPHP\UrlInterface          $url
 * @property \ManaPHP\RouterInterface       $router
 */
class Response extends Component implements ResponseInterface
{
    /**
     * @var bool
     */
    protected $_sent = false;

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
        $this->_sent = false;
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
    public function setStatus($code, $text)
    {
        $this->_status = $code . ' ' . $text;

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

        $date->setTimezone(new \DateTimeZone('UTC'));
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
            if (!isset($content['code']) && !isset($content['data'])) {
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
            $content = ['code' => -1, 'message' => $content->getMessage()];
        }

        $this->_content = is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;

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
     * Check if the response is already sent
     *
     * @return bool
     */
    public function isSent()
    {
        return $this->_sent;
    }

    /**
     * Sends headers to the client
     *
     * @return static
     */
    public function sendHeaders()
    {
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

        header('X-Response-Time: ' . sprintf('%.3f', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']));

        return $this;
    }

    /**
     * Prints out HTTP response to the client
     *
     * @return static
     * @throws \ManaPHP\Http\Response\Exception
     */
    public function send()
    {
        if ($this->_sent === true) {
            throw new ResponseException('Response was already sent');
        }

        $this->fireEvent('response:beforeSend');

        if ($this->_file) {
            if (!$this->filesystem->fileExists($this->_file)) {
                throw new ResponseException(['Sent file is not exists: `:file`', 'file' => $this->_file]);
            }
            $this->setHeader('Content-Length', $this->filesystem->fileSize($this->_file));
        }

        if (!headers_sent()) {
            $this->sendHeaders();
        }

        if ($this->_content !== null) {
            echo $this->_content;
        } else {
            if ($this->_file) {
                readfile($this->alias->resolve($this->_file));
            }
        }

        $this->_sent = true;

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
    public function setFileToSend($file, $attachmentName = null)
    {
        if ($attachmentName === null) {
            $attachmentName = basename($file);
        }

        $this->_file = $file;

        $this->setAttachment($attachmentName);

        return $this;
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
     * @param string       $attachmentName
     * @param array|string $fields
     *
     * @return static
     */
    public function setCsvContent($rows, $attachmentName, $fields = null)
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }

        if (pathinfo($attachmentName, PATHINFO_EXTENSION) !== 'csv') {
            $attachmentName .= '.csv';
        }

        $this->setAttachment($attachmentName);

        $file = fopen('php://temp', 'rb+');

        fprintf($file, "\xEF\xBB\xBF");

        if ($fields !== null) {
            if (strpos($fields[0], 'ID') === 0) {
                $fields[0] = strtolower($fields[0]);
            }

            fputcsv($file, $fields);
        }

        foreach ($rows as $row) {
            if (is_object($row)) {
                if (method_exists($row, 'toArray')) {
                    $data = $row->toArray();
                } else {
                    $data = (array)$row;
                }
            } elseif (!is_array($row)) {
                $data = [$row];
            } else {
                $data = $row;
            }

            fputcsv($file, $data);
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