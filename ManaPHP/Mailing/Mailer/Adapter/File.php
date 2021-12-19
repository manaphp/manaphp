<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Helper\LocalFS;
use ManaPHP\Mailing\AbstractMailer;
use ManaPHP\Mailing\Mailer\Message;

class File extends AbstractMailer
{
    protected ?string $file = null;
    protected bool $pretty = false;

    public function __construct(array $options = [])
    {
        if (isset($options['file'])) {
            $this->file = $options['file'];
        }
        if (isset($options['pretty'])) {
            $this->pretty = (bool)$options['pretty'];
        }

        if (isset($options['log'])) {
            $this->log = $options['log'];
        }

        if (isset($options['from'])) {
            $this->from = $options['from'];
        }

        if (isset($options['to'])) {
            $this->to = $options['to'];
        }
    }

    protected function sendInternal(Message $message, ?array &$failedRecipients = null): int
    {
        if ($this->pretty) {
            $data = str_repeat('=', 20) . date('Y-m-d H:i:s') . str_repeat('=', 20)
                . PHP_EOL
                . json_stringify($message, JSON_PRETTY_PRINT)
                . PHP_EOL;
        } else {
            $data = json_stringify($message) . PHP_EOL;
        }

        LocalFS::fileAppend($this->file ?? '@data/fileMailer/mailer_' . date('ymd') . '.log', $data);

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}