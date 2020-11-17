<?php

namespace ManaPHP\Mailing\Mailer;

use JsonSerializable;
use ManaPHP\Di;

class Message implements JsonSerializable
{
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 4;
    const PRIORITY_LOWEST = 5;

    /**
     * @var \ManaPHP\Mailing\MailerInterface
     */
    protected $_mailer;

    /**
     * @var string
     */
    protected $_date;

    /**
     * @var string
     */
    protected $_subject;

    /**
     * @var string|array
     */
    protected $_to = [];

    /**
     * @var int
     */
    protected $_priority;

    /**
     * @var string
     */
    protected $_charset = 'utf-8';

    /**
     * @var array
     */
    protected $_from = [];

    /**
     * @var array
     */
    protected $_replay_to = [];

    /**
     * @var array
     */
    protected $_cc = [];

    /**
     * @var array
     */
    protected $_bcc = [];

    /**
     * @var string
     */
    protected $_html_body;

    /**
     * @var string
     */
    protected $_text_body;

    /**
     * @var array
     */
    protected $_attachments = [];

    /**
     * @var array
     */
    protected $_embedded_files = [];

    /**
     * @param array $message
     */
    public function __construct($message = null)
    {
        if ($message) {
            foreach ($message as $k => $v) {
                $field = "_$k";
                $this->$field = $v;
            }
        } else {
            $this->_date = date(DATE_ATOM);
        }
    }

    /**
     * @param \ManaPHP\Mailing\MailerInterface $mailer
     *
     * @return static
     */
    public function setMailer($mailer)
    {
        $this->_mailer = $mailer;

        return $this;
    }

    /**
     * @return \ManaPHP\Mailing\MailerInterface
     */
    public function getMailer()
    {
        return $this->_mailer;
    }

    /**
     * @param string $charset
     *
     * @return static
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;

        return $this;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * @param string|array $from
     *
     * @return static
     */
    public function setFrom($from)
    {
        $this->_from = is_string($from) ? [$from] : $from;

        return $this;
    }

    /**
     * @return array
     */
    public function getFrom()
    {
        return $this->_from;
    }

    /**
     * @param string|array $to
     *
     * @return static
     */
    public function setTo($to)
    {
        $this->_to = is_string($to) ? [$to] : $to;

        return $this;
    }

    /**
     * @return array
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * @param string|array $replyTo
     *
     * @return static
     */
    public function setReplyTo($replyTo)
    {
        $this->_replay_to = is_string($replyTo) ? [$replyTo] : $replyTo;

        return $this;
    }

    /**
     * @return array
     */
    public function getReplyTo()
    {
        return $this->_replay_to;
    }

    /**
     * @param string|array $cc
     *
     * @return static
     */
    public function setCc($cc)
    {
        $this->_cc = is_string($cc) ? [$cc] : $cc;

        return $this;
    }

    /**
     * @return array
     */
    public function getCc()
    {
        return $this->_cc;
    }

    /**
     * @param array|string $bcc
     *
     * @return static
     */
    public function setBcc($bcc)
    {
        $this->_bcc = is_string($bcc) ? [$bcc] : $bcc;

        return $this;
    }

    /**
     * @return array
     */
    public function getBcc()
    {
        return $this->_bcc;
    }

    /**
     * @param string $subject
     *
     * @return static
     */
    public function setSubject($subject)
    {
        $this->_subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->_subject;
    }

    /**
     * @param string $html
     * @param string $text
     *
     * @return static
     */
    public function setBody($html, $text = null)
    {
        $this->_html_body = $html;
        $this->_text_body = $text;

        return $this;
    }

    /**
     * @param string|array $body
     *
     * @return static
     */
    public function setHtmlBody($body)
    {
        if (is_array($body)) {
            $template = $body[0];

            $vars = $body;
            unset($vars[0]);

            if ($template[0] !== '@') {
                $template = "@views/Mail/$template";
            }

            $body = Di::getDefault()->renderer->renderFile($template, $vars);
        }

        $this->_html_body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->_html_body;
    }

    /**
     * @param string $body
     *
     * @return static
     */
    public function setTextBody($body)
    {
        $this->_text_body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        return $this->_text_body;
    }

    /**
     * @param int $priority
     *
     * @return static
     */
    public function setPriority($priority)
    {
        $this->_priority = $priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->_priority;
    }

    /**
     * @return string
     */
    public function getRandomId()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * @param string $file
     * @param string $name
     *
     *
     * @return static
     */
    public function addAttachment($file, $name = null)
    {
        $this->_attachments[] = ['file' => $file, 'name' => $name ?: basename($file)];
        return $this;
    }

    /**
     * @return array[]
     */
    public function getAttachments()
    {
        return $this->_attachments;
    }

    /**
     * @param string $file
     * @param string $name
     *
     * @return string
     */
    public function addEmbeddedFile($file = null, $name = null)
    {
        if (!$name) {
            $name = basename($file);
        }

        if (preg_match('#^[\w.]+$#', $name)) {
            $cid = $name;
        } else {
            $cid = md5($name) . '.' . pathinfo($name, PATHINFO_EXTENSION);
        }

        $this->_embedded_files[] = ['file' => $file, 'name' => $name, 'cid' => $cid];

        return 'cid:' . $cid;
    }

    /**
     * @param string $file
     * @param string $name
     *
     * @return string
     */
    public function embed($file, $name = null)
    {
        return $this->addEmbeddedFile($file, $name);
    }

    /**
     * @return array[]
     */
    public function getEmbeddedFiles()
    {
        return $this->_embedded_files;
    }

    /**
     * @param array $failedRecipients
     *
     * @return int
     */
    public function send(&$failedRecipients = null)
    {
        return $this->_mailer->send($this, $failedRecipients);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [];
        foreach (get_object_vars($this) as $k => $v) {
            if ($k === '_mailer' || $v === null) {
                continue;
            }
            $data[substr($k, 1)] = $v;
        }

        return $data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
