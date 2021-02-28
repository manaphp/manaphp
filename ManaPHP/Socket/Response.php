<?php

namespace ManaPHP\Socket;

use JsonSerializable;
use ManaPHP\Component;
use Throwable;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class ResponseContext
{
    public $content;
}

/**
 * @property-read \ManaPHP\Socket\ResponseContext $context
 */
class Response extends Component implements ResponseInterface
{
    public function getContext()
    {
        return $this->context;
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
        $context = $this->context;

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
        return $this->self->setJsonContent(['code' => 0, 'message' => $message]);
    }

    /**
     * @param string $message
     * @param int    $code
     *
     * @return static
     */
    public function setJsonError($message, $code = 1)
    {
        return $this->self->setJsonContent(['code' => $code, 'message' => $message]);
    }

    /**
     * @param mixed  $data
     * @param string $message
     *
     * @return static
     */
    public function setJsonData($data, $message = '')
    {
        return $this->self->setJsonContent(['code' => 0, 'message' => $message, 'data' => $data]);
    }

    /**
     * Sets socket response body. The parameter is automatically converted to JSON
     *
     * @param array|\JsonSerializable|string|\Exception $content
     *
     * @return static
     */
    public function setJsonContent($content)
    {
        $context = $this->context;

        if (is_array($content) || is_string($content)) {
            null;
        } elseif ($content instanceof JsonSerializable) {
            $content = ['code' => 0, 'message' => '', 'data' => $content];
        } elseif ($content instanceof \ManaPHP\Exception) {
            $content = $content->getJson();
        } elseif ($content instanceof Throwable) {
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
        return $this->context->content;
    }
}