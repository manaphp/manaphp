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
     * @var string
     */
    protected $_origin;

    /**
     * @var bool
     */
    protected $_credentials = true;

    /**
     * CorsPlugin constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['max_age'])) {
            $this->_max_age = $options['max_age'];
        }

        if (isset($options['origin'])) {
            $this->_origin = $options['origin'];
        }

        if (isset($options['credentials'])) {
            $this->_credentials = $options['credentials'];
        }

        $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
    }

    public function onRequestBegin()
    {
        $origin = $this->request->getServer('HTTP_ORIGIN');
        $host = $this->request->getServer('HTTP_HOST');

        if ($origin !== '' && $origin !== $host) {
            if ($this->_origin) {
                $allow_origin = $this->_origin;
            } elseif ($this->configure->env === 'prod') {
                $origin_pos = strpos($origin, '.');
                $host_pos = strpos($host, '.');

                if (($origin_pos !== false && $host_pos !== false)
                    && substr($origin, $origin_pos) === substr($host, $host_pos)
                ) {
                    $allow_origin = $origin;
                } else {
                    $allow_origin = '*';
                }
            } else {
                $allow_origin = $origin;
            }

            $allow_headers = 'Origin, Accept, Authorization, Content-Type, X-Requested-With';
            $allow_methods = 'HEAD,GET,POST,PUT,DELETE';
            $this->response
                ->setHeader('Access-Control-Allow-Origin', $allow_origin)
                ->setHeader('Access-Control-Allow-Credentials', $this->_credentials ? 'true' : 'false')
                ->setHeader('Access-Control-Allow-Headers', $allow_headers)
                ->setHeader('Access-Control-Allow-Methods', $allow_methods)
                ->setHeader('Access-Control-Max-Age', $this->_max_age);
        }

        if ($this->request->isOptions()) {
            throw new AbortException();
        }
    }
}