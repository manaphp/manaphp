<?php
namespace ManaPHP\Plugins;

use ManaPHP\Exception\AbortException;
use ManaPHP\Plugin;

class CorsPlugin extends Plugin
{
    /**
     * @var int
     */
    protected $_max_age = 86400;

    /**
     * CorsPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if (is_array($options)) {
            if (isset($options['max_age'])) {
                $this->_max_age = $options['max_age'];
            }
        }
    }

    public function init()
    {
        $this->attachEvent('app:beginRequest', [$this, 'onBeginRequest']);
    }

    public function onBeginRequest()
    {
        $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Credentials', 'true')
            ->setHeader('Access-Control-Allow-Headers', 'Origin, Accept, Authorization, Content-Type, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'HEAD,GET,POST,PUT,DELETE')
            ->setHeader('Access-Control-Max-Age', $this->_max_age);

        if ($this->request->isOptions()) {
            throw new AbortException();
        }
    }
}