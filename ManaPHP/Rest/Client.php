<?php

namespace ManaPHP\Rest;

class Client extends \ManaPHP\Http\Client implements ClientInterface
{
    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (!isset($options['engine'])) {
            $options['engine'] = 'ManaPHP\Http\Client\Engine\Fopen';
        }

        parent::__construct($options);
    }
}