<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Coroutine\Context\Inseparable;

class SmtpContext implements Inseparable
{
    public mixed $socket;
    public string $file;
}