<?php
declare(strict_types=1);

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class NonCloneableException extends Exception
{
    public function __construct(object $object)
    {
        parent::__construct(['`:class` is not cloneable', 'class' => get_class($object)]);
    }
}