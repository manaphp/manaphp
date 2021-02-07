<?php

namespace ManaPHP\Http;

use ManaPHP\Event\EventArgs;
use ManaPHP\Http\Client\Request;
use ManaPHP\Plugin;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class RequestIdPlugin extends Plugin
{
    /**
     * @var bool
     */
    protected $enabled = true;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->enabled = (bool)$options['enabled'];
        }

        if (MANAPHP_CLI) {
            $this->enabled = false;
        }

        if ($this->enabled) {
            $this->attachEvent('httpClient:requesting', [$this, 'onHttpClientRequesting']);
        }
    }

    /**
     * @param EventArgs $eventArgs
     *
     * @return void
     */
    public function onHttpClientRequesting(EventArgs $eventArgs)
    {
        /** @var Request $request */
        $request = $eventArgs->data['request'];

        if (!isset($request->headers['X-Request-Id'])) {
            $request->headers['X-Request-Id'] = $this->request->getRequestId();
        }
    }
}