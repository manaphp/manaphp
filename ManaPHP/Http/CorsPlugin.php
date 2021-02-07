<?php

namespace ManaPHP\Http;

use ManaPHP\Exception\AbortException;
use ManaPHP\Plugin;

/**
 * @property-read \ManaPHP\Configuration\Configure $configure
 */
class CorsPlugin extends Plugin
{
    /**
     * @var int
     */
    protected $max_age = 86400;

    /**
     * @var string
     */
    protected $origin;

    /**
     * @var bool
     */
    protected $credentials = true;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['max_age'])) {
            $this->max_age = $options['max_age'];
        }

        if (isset($options['origin'])) {
            $this->origin = $options['origin'];
        }

        if (isset($options['credentials'])) {
            $this->credentials = $options['credentials'];
        }

        $this->attachEvent('request:begin', [$this, 'onRequestBegin']);
    }

    /**
     * @return void
     */
    public function onRequestBegin()
    {
        $origin = $this->request->getServer('HTTP_ORIGIN');
        $host = $this->request->getServer('HTTP_HOST');

        if ($origin !== '' && $origin !== $host) {
            if ($this->origin) {
                $allow_origin = $this->origin;
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
                ->setHeader('Access-Control-Allow-Credentials', $this->credentials ? 'true' : 'false')
                ->setHeader('Access-Control-Allow-Headers', $allow_headers)
                ->setHeader('Access-Control-Allow-Methods', $allow_methods)
                ->setHeader('Access-Control-Max-Age', $this->max_age);
        }

        if ($this->request->isOptions()) {
            throw new AbortException();
        }
    }
}