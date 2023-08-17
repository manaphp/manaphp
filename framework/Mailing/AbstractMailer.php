<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Event\EventTrait;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Mailing\Mailer\Message;

abstract class AbstractMailer implements MailerInterface
{
    use EventTrait;

    #[Inject] protected MakerInterface $maker;

    #[Value] protected ?string $log = null;
    #[Value] protected ?string $from = null;
    #[Value] protected ?string $to = null;

    public function compose(): Message
    {
        $message = $this->maker->make(Message::class);

        $message->setMailer($this);

        if ($this->from) {
            $message->setFrom($this->from);
        }
        if ($this->to) {
            $message->setFrom($this->to);
        }

        return $message;
    }

    abstract protected function sendInternal(Message $message, ?array &$failedRecipients = null): int;

    public function send(Message $message, ?array &$failedRecipients = null): int
    {
        if ($this->log) {
            LocalFS::fileAppend($this->log, json_stringify($message) . PHP_EOL);
        }

        $failedRecipients = [];

        $message->setMailer($this);
        $this->fireEvent('mailer:sending', compact('message'));
        $r = $this->sendInternal($message, $failedRecipients);
        $this->fireEvent('mailer:sent', compact('message', 'failedRecipients'));

        return $r;
    }
}