<?php

namespace ManaPHP\Identifying;

use ManaPHP\Coroutine\Context\Stickyable;

class IdentityContext implements Stickyable
{
    /**
     * @var array
     */
    public $claims = [];
}