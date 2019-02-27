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
     * @var bool
     */
    protected $_force = false;

    /**
     * @var string
     */
    protected $_origin = '*';

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

            if (isset($options['force'])) {
                $this->_force = (bool)$options['force'];
            }

            if (isset($options['origin'])) {
                $this->_origin = $options['origin'];
            }
        }
    }

    public function init()
    {
        $this->attachEvent('app:beginRequest', [$this, 'onBeginRequest']);
    }

    public function onBeginRequest()
    {
        if ($this->_force || isset($_SERVER['HTTP_ORIGIN'])) {
            $this->response
                ->setHeader('Access-Control-Allow-Origin', $this->_origin)
                ->setHeader('Access-Control-Allow-Credentials', 'true')
                ->setHeader('Access-Control-Allow-Headers', 'Origin, Accept, Authorization, Content-Type, X-Requested-With')
                ->setHeader('Access-Control-Allow-Methods', 'HEAD,GET,POST,PUT,DELETE')
                ->setHeader('Access-Control-Max-Age', $this->_max_age);
        }

        if ($this->request->isOptions()) {
            throw new AbortException();
        }
    }
}