<?php
declare(strict_types=1);

namespace ManaPHP\Rest;

class Client extends \ManaPHP\Http\Client implements ClientInterface
{
    public function __construct(array $options = [])
    {
        if (!isset($options['engine'])) {
            $options['engine'] = 'ManaPHP\Http\Client\Engine\Fopen';
        }

        parent::__construct($options);
    }
}