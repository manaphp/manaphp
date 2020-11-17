<?php

namespace ManaPHP\Http;

use ManaPHP\Event\EventArgs;
use ManaPHP\Plugin;

class EtagPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * @var string
     */
    protected $_algo = 'md5';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->_enabled = (bool)$options['enabled'];
        }

        if (isset($options['algo'])) {
            $this->_algo = $options['algo'];
        }

        if ($this->_enabled) {
            $this->attachEvent('response:sending', [$this, 'onResponseSending']);
        }
    }

    public function onResponseSending(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\ResponseContext $response */
        $response = $eventArgs->data;
        if ($response->status_code !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        if (isset($response->headers['ETag'])) {
            $etag = $response->headers['ETag'];
        } else {
            $etag = hash($this->_algo, $response->content);
            $response->headers['ETag'] = $etag;
        }

        $if_none_match = $this->request->getIfNoneMatch();
        if ($if_none_match === $etag) {
            unset($response->headers['ETag']);

            $response->status_code = 304;
            $response->status_text = 'Not Modified';
        }
    }
}