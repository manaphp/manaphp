<?php

namespace ManaPHP\Mailing\Mailer;

use JsonSerializable;
use ManaPHP\Di\Container;
use ManaPHP\Html\RendererInterface;

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
    protected $mailer;

    /**
     * @var string
     */
    protected $date;

    /**
     * @var string
     */
    protected $subject;

    /**
     * @var string|array
     */
    protected $to = [];

    /**
     * @var int
     */
    protected $priority;

    /**
     * @var string
     */
    protected $charset = 'utf-8';

    /**
     * @var array
     */
    protected $from = [];

    /**
     * @var array
     */
    protected $replay_to = [];

    /**
     * @var array
     */
    protected $cc = [];

    /**
     * @var array
     */
    protected $bcc = [];

    /**
     * @var string
     */
    protected $html_body;

    /**
     * @var string
     */
    protected $text_body;

    /**
     * @var array
     */
    protected $attachments = [];

    /**
     * @var array
     */
    protected $embedded_files = [];

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
            $this->date = date(DATE_ATOM);
        }
    }

    /**
     * @param \ManaPHP\Mailing\MailerInterface $mailer
     *
     * @return static
     */
    public function setMailer($mailer)
    {
        $this->mailer = $mailer;

        return $this;
    }

    /**
     * @return \ManaPHP\Mailing\MailerInterface
     */
    public function getMailer()
    {
        return $this->mailer;
    }

    /**
     * @param string $charset
     *
     * @return static
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * @param string|array $from
     *
     * @return static
     */
    public function setFrom($from)
    {
        $this->from = is_string($from) ? explode(',', $from) : $from;

        return $this;
    }

    /**
     * @return array
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param string|array $to
     *
     * @return static
     */
    public function setTo($to)
    {
        $this->to = is_string($to) ? explode(',', $to) : $to;

        return $this;
    }

    /**
     * @return array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param string|array $replyTo
     *
     * @return static
     */
    public function setReplyTo($replyTo)
    {
        $this->replay_to = is_string($replyTo) ? explode(',', $replyTo) : $replyTo;

        return $this;
    }

    /**
     * @return array
     */
    public function getReplyTo()
    {
        return $this->replay_to;
    }

    /**
     * @param string|array $cc
     *
     * @return static
     */
    public function setCc($cc)
    {
        $this->cc = is_string($cc) ? explode(',', $cc) : $cc;

        return $this;
    }

    /**
     * @return array
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param array|string $bcc
     *
     * @return static
     */
    public function setBcc($bcc)
    {
        $this->bcc = is_string($bcc) ? explode(',', $bcc) : $bcc;

        return $this;
    }

    /**
     * @return array
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @param string $subject
     *
     * @return static
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $html
     * @param string $text
     *
     * @return static
     */
    public function setBody($html, $text = null)
    {
        $this->html_body = $html;
        $this->text_body = $text;

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

            $body = Container::getDefault()->get(RendererInterface::class)->renderFile($template, $vars);
        }

        $this->html_body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlBody()
    {
        return $this->html_body;
    }

    /**
     * @param string $body
     *
     * @return static
     */
    public function setTextBody($body)
    {
        $this->text_body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getTextBody()
    {
        return $this->text_body;
    }

    /**
     * @param int $priority
     *
     * @return static
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
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
        $this->attachments[] = ['file' => $file, 'name' => $name ?: basename($file)];
        return $this;
    }

    /**
     * @return array[]
     */
    public function getAttachments()
    {
        return $this->attachments;
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

        $this->embedded_files[] = ['file' => $file, 'name' => $name, 'cid' => $cid];

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
        return $this->embedded_files;
    }

    /**
     * @param array $failedRecipients
     *
     * @return int
     */
    public function send(&$failedRecipients = null)
    {
        return $this->mailer->send($this, $failedRecipients);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $data = [];
        foreach (get_object_vars($this) as $k => $v) {
            if ($k === 'mailer' || $v === null) {
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
