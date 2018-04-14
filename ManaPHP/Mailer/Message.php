<?php
namespace ManaPHP\Mailer;

class Message implements \JsonSerializable
{
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 4;
    const PRIORITY_LOWEST = 5;

    /**
     * @var \ManaPHP\MailerInterface
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
    protected $_to;

    /**
     * @var int
     */
    protected $_priority;

    /**
     * @var string
     */
    protected $_charset = 'utf-8';

    /**
     * @var string|array
     */
    protected $_from;

    /**
     * @var string
     */
    protected $_replayTo;

    /**
     * @var string|array
     */
    protected $_cc;

    /**
     * @var string|array
     */
    protected $_bcc;

    /**
     * @var string
     */
    protected $_contentType;

    /**
     * @var string
     */
    protected $_body;

    /**
     * Message constructor.
     *
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
     * @param \ManaPHP\MailerInterface $mailer
     *
     * @return static
     */
    public function setMailer($mailer)
    {
        $this->_mailer = $mailer;

        return $this;
    }

    /**
     * @return \ManaPHP\MailerInterface
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
        $this->_from = $from;

        return $this;
    }

    /**
     * @return string|array
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
        $this->_to = $to;

        return $this;
    }

    /**
     * @return string|array
     */
    public function getTo()
    {
        return $this->_to;
    }

    /**
     * @param string $replyTo
     *
     * @return static
     */
    public function setReplyTo($replyTo)
    {
        $this->_replayTo = $replyTo;

        return $this;
    }

    /**
     * @return string
     */
    public function getReplyTo()
    {
        return $this->_replayTo;
    }

    /**
     * @param string|array $cc
     *
     * @return static
     */
    public function setCc($cc)
    {
        $this->_cc = $cc;

        return $this;
    }

    /**
     * @return string|array
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
        $this->_bcc = $bcc;

        return $this;
    }

    /**
     * @return string|array
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
     * @param string $body
     * @param string $contentType
     *
     * @return static
     */
    public function setBody($body, $contentType)
    {
        $this->_body = $body;
        $this->_contentType = $contentType;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->_body;
    }

    public function getContentType()
    {
        return $this->_contentType;
    }

    /**
     * @param string $body
     *
     * @return static
     */
    public function setTextBody($body)
    {
        return $this->setBody($body, 'text/plain');
    }

    /**
     * @param string $html
     *
     * @return static
     */
    public function setHtmlBody($html)
    {
        return $this->setBody($html, 'text/html');
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
