<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Contextor\ContextInseparable;

class SmtpContext implements ContextInseparable
{
    public mixed $socket = null;
    public string $file;
}