<?php

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class NonCloneableException extends Exception
{
    /**
     * NonCloneableException constructor.
     *
     * @param object $object
     */
    public function __construct($object)
    {
        parent::__construct(['`:class` is not cloneable', 'class' => get_class($object)]);
    }
}