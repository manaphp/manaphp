<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer\Adapter;

use ManaPHP\Helper\LocalFS;
use ManaPHP\Mailing\AbstractMailer;
use ManaPHP\Mailing\Mailer\Message;

class File extends AbstractMailer
{
    protected ?string $file;
    protected bool $pretty;

    public function __construct(?string $file, bool $pretty = false,
        ?string $log = null, ?string $from = null, ?string $to = null
    ) {
        $this->file = $file;
        $this->pretty = $pretty;

        $this->log = $log;
        $this->from = $from;
        $this->to = $to;
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

        LocalFS::fileAppend($this->file ?? '@runtime/fileMailer/mailer_' . date('ymd') . '.log', $data);

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}