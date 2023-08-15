<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Mailing\Mailer\Message;

class MessageMaker implements MessageMakerInterface
{
    #[Inject] protected MakerInterface $maker;

    public function make(): mixed
    {
        return $this->maker->make(Message::class);
    }
}