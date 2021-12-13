<?php
declare(strict_types=1);

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class AbortException extends Exception
{
    public function __construct()
    {
        parent::__construct();
    }
}