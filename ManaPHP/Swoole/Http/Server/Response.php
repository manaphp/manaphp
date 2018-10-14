<?php
namespace ManaPHP\Swoole\Http\Server;

use ManaPHP\Http\Response\Exception as ResponseException;

/**
 * Class Response
 * @package ManaPHP\Swoole\Http\Server
 * @property-read \ManaPHP\Swoole\Http\Server $swooleHttpServer
 */
class Response extends \ManaPHP\Http\Response
{
    /**
     * Sends headers to the client
     *
     * @return static
     */
    public function sendHeaders()
    {
        $swooleHttpServer = $this->swooleHttpServer;

        if ($this->_status) {
            $swooleHttpServer->setStatus($this->_status);
        }

        $headers = [];
        foreach ($this->_headers as $header => $value) {
            if ($value !== null) {
                $headers[$header] = $value;
            } else {
                $parts = explode(':', $header, 2);
                $headers[$parts[0]] = $parts[1];
            }
        }
        $headers['X-Response-Time'] = sprintf('%.3f', microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

        $swooleHttpServer->sendHeaders($headers);

        return $this;
    }

    /**
     * @return $this|\ManaPHP\Http\Response
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

        $this->sendHeaders();

        if ($this->_content !== null) {
            $this->swooleHttpServer->sendContent($this->_content);
        } else {
            if ($this->_file) {
                $this->swooleHttpServer->sendFile($this->_file);
            }
        }

        $this->_sent = true;

        $this->fireEvent('response:afterSend');

        return $this;
    }
}