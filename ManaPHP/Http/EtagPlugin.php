<?php

namespace ManaPHP\Http;

use ManaPHP\Event\EventArgs;
use ManaPHP\Plugin;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class EtagPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @var string
     */
    protected $algo = 'md5';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->enabled = (bool)$options['enabled'];
        }

        if (isset($options['algo'])) {
            $this->algo = $options['algo'];
        }

        if ($this->enabled) {
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
            $etag = hash($this->algo, $responseContext->content);
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