<?php

namespace ManaPHP\Rest;

class Factory extends \ManaPHP\Http\Factory
{
    public function __construct()
    {
        parent::__construct();

        $this->definitions = array_merge(
            $this->definitions, [
                'errorHandler' => 'ManaPHP\Rest\ErrorHandler',
                'identity'     => 'ManaPHP\Identifying\Identity\Adapter\Jwt'
            ]
        );
    }
}