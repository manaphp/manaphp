<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\MakerInterface;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Mailing\Mailer\Event\MailerSending;
use ManaPHP\Mailing\Mailer\Event\MailerSent;
use ManaPHP\Mailing\Mailer\Message;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractMailer implements MailerInterface
{

    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected MakerInterface $maker;

    #[Autowired] protected ?string $log;
    #[Autowired] protected ?string $from;
    #[Autowired] protected ?string $to;

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
        $this->eventDispatcher->dispatch(new MailerSending($this, $message));
        $r = $this->sendInternal($message, $failedRecipients);

        $this->eventDispatcher->dispatch(new MailerSent($this, $message, $failedRecipients));

        return $r;
    }
}