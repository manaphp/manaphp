<?php
declare(strict_types=1);

namespace ManaPHP\Mailing\Mailer;

use JsonSerializable;
use ManaPHP\Helper\Container;
use ManaPHP\Mailing\MailerInterface;
use ManaPHP\Rendering\RendererInterface;
use function is_array;
use function is_string;

class Message implements JsonSerializable
{
    public const PRIORITY_HIGHEST = 1;
    public const PRIORITY_HIGH = 2;
    public const PRIORITY_NORMAL = 3;
    public const PRIORITY_LOW = 4;
    public const PRIORITY_LOWEST = 5;

    protected MailerInterface $mailer;
    protected string $date;
    protected string $subject;
    protected string|array $to = [];
    protected int $priority;
    protected string $charset = 'utf-8';
    protected array $from = [];
    protected array $replay_to = [];
    protected array $cc = [];
    protected array $bcc = [];
    protected string $html_body = '';
    protected string $text_body = '';
    protected array $attachments = [];
    protected array $embedded_files = [];

    public function __construct(?array $message = null)
    {
        if ($message) {
            foreach ($message as $field => $value) {
                $this->$field = $value;
            }
        } else {
            $this->date = date(DATE_ATOM);
        }
    }

    public function setMailer(MailerInterface $mailer): static
    {
        $this->mailer = $mailer;

        return $this;
    }

    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setFrom(string|array $from): static
    {
        $this->from = is_string($from) ? explode(',', $from) : $from;

        return $this;
    }

    public function getFrom(): array
    {
        return $this->from;
    }

    public function setTo(string|array $to): static
    {
        $this->to = is_string($to) ? explode(',', $to) : $to;

        return $this;
    }

    public function getTo(): array
    {
        return $this->to;
    }

    public function setReplyTo(string|array $replyTo): static
    {
        $this->replay_to = is_string($replyTo) ? explode(',', $replyTo) : $replyTo;

        return $this;
    }

    public function getReplyTo(): array
    {
        return $this->replay_to;
    }

    public function setCc(string|array $cc): static
    {
        $this->cc = is_string($cc) ? explode(',', $cc) : $cc;

        return $this;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function setBcc(string|array $bcc): static
    {
        $this->bcc = is_string($bcc) ? explode(',', $bcc) : $bcc;

        return $this;
    }

    public function getBcc(): array
    {
        return $this->bcc;
    }

    /**
     * @param string $subject
     *
     * @return static
     */
    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string  $html
     * @param ?string $text
     *
     * @return static
     */
    public function setBody(string $html, ?string $text = null): static
    {
        $this->html_body = $html;
        $this->text_body = $text;

        return $this;
    }

    public function setHtmlBody(string|array $body): static
    {
        if (is_array($body)) {
            $template = $body[0];

            $vars = $body;
            unset($vars[0]);

            if ($template[0] !== '@') {
                $template = "@views/Mail/$template";
            }

            $body = Container::get(RendererInterface::class)->renderFile($template, $vars);
        }

        $this->html_body = $body;

        return $this;
    }

    public function getHtmlBody(): string
    {
        return $this->html_body;
    }

    /**
     * @param string $body
     *
     * @return static
     */
    public function setTextBody(string $body): static
    {
        $this->text_body = $body;

        return $this;
    }

    /**
     * @return string
     */
    public function getTextBody(): string
    {
        return $this->text_body;
    }

    public function setPriority(int $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getRandomId(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function addAttachment(string $file, ?string $name = null): static
    {
        $this->attachments[] = ['file' => $file, 'name' => $name ?: basename($file)];
        return $this;
    }

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    public function addEmbeddedFile(string $file, ?string $name = null): string
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

    public function embed(string $file, ?string $name = null): string
    {
        return $this->addEmbeddedFile($file, $name);
    }

    public function getEmbeddedFiles(): array
    {
        return $this->embedded_files;
    }

    public function send(?array &$failedRecipients = null): int
    {
        return $this->mailer->send($this, $failedRecipients);
    }

    public function toArray(): array
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

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
