<?php

namespace ManaPHP\Rest;

/**
 * Class ManaPHP\Rest\Controller
 *
 * @package controller
 */
abstract class Controller extends \ManaPHP\Http\Controller
{
    public function getVerbs()
    {
        return [
            'index' => 'GET',
            'detail' => 'GET',
            'create' => 'POST',
            'update' => ['POST', 'PUT'],
            'delete' => ['DELETE', 'POST'],
        ];
    }
}