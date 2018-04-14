<?php
namespace ManaPHP\Mailer\Adapter;

use ManaPHP\Mailer;

class File extends Mailer
{
    /**
     * @var string
     */
    protected $_file;

    /**
     * @var bool
     */
    protected $_pretty = false;

    /**
     * File constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $this->_file = $options;
            parent::__construct([]);
        } else {
            if (isset($options['file'])) {
                $this->_file = $options['file'];
            }
            if (isset($options['pretty'])) {
                $this->_pretty = (bool)$options['pretty'];
            }
            parent::__construct($options);
        }
    }

    /**
     * @param \ManaPHP\Mailer\Message $message
     * @param array                   $failedRecipients
     *
     * @return int
     */
    protected function _send($message, &$failedRecipients = null)
    {
        if ($this->_pretty) {
            $data = str_repeat('=', 20) . date('Y-m-d H:i:s') . str_repeat('=', 20)
                . PHP_EOL
                . json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                . PHP_EOL;
        } else {
            $data = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
        }

        $this->filesystem->fileAppend($this->_file ?: '@data/fileMailer/mailer_' . date('ymd') . '.log', $data);

        return count($message->getTo()) + count($message->getCc()) + count($message->getBcc());
    }
}