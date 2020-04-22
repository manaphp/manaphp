<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class MethodNotAllowedHttpException extends Exception
{
    /**
     * MethodNotAllowedHttpException constructor.
     *
     * @param array $verbs
     */
    public function __construct($verbs)
    {
        parent::__construct('This URL can only handle the following request methods: ' . implode(', ', $verbs));
    }

    public function getStatusCode()
    {
        return 405;
    }
}