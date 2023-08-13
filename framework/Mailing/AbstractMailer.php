<?php
declare(strict_types=1);

namespace ManaPHP\Mailing;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\FactoryInterface;
use ManaPHP\Event\EventTrait;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Mailing\Mailer\Message;

abstract class AbstractMailer extends Component implements MailerInterface
{
    use EventTrait;

    #[Inject] protected FactoryInterface $factory;

    protected ?string $log = null;
    protected ?string $from = null;
    protected ?string $to = null;

    public function compose(): Message
    {
        $message = $this->factory->make('ManaPHP\Mailing\Mailer\Message');

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