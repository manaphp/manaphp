<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Mailing\AbstractMailer;
use ManaPHP\Mailing\Mailer\Message;

class File extends AbstractMailer
{
    #[Autowired] protected ?string $file;
    #[Autowired] protected bool $pretty = false;

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

        LocalFS::fileAppend($this->file ?? '@runtime/mailer/file_' . date('ymd') . '.log', $data);

        return \count($message->getTo()) + \count($message->getCc()) + \count($message->getBcc());
    }
}