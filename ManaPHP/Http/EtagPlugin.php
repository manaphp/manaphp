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

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onResponseSending(EventArgs $eventArgs)
    {
        /** @var \ManaPHP\Http\ResponseContext $responseContext */
        $responseContext = $eventArgs->data['context'];
        if ($responseContext->status_code !== 200 || !in_array($this->request->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }

        if (isset($responseContext->headers['ETag'])) {
            $etag = $responseContext->headers['ETag'];
        } else {
            $etag = hash($this->_algo, $responseContext->content);
            $responseContext->headers['ETag'] = $etag;
        }

        $if_none_match = $this->request->getIfNoneMatch();
        if ($if_none_match === $etag) {
            unset($responseContext->headers['ETag']);

            $responseContext->status_code = 304;
            $responseContext->status_text = 'Not Modified';
        }
    }
}