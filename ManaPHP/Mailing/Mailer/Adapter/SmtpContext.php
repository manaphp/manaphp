<?php

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Coroutine\Context\Inseparable;

class SmtpContext implements Inseparable
{
    public $socket;
    public $file;
}