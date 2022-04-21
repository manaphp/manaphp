<?php
declare(strict_types=1);

namespace ManaPHP\Http\Client\Response;

class Cookie
{
    public string $name;
    public string $value;
    public ?int $expires = null;
    public ?string $path = null;
    public ?string $domain = null;
    public ?bool $secure = null;
    public ?bool $httponly = null;

    public function __construct(string $cookie)
    {
        if (($pos = strpos($cookie, ';')) === false) {
            list($this->name, $this->value) = explode('=', $cookie, 2);
        } else {
            list($this->name, $this->value) = explode('=', substr($cookie, 0, $pos), 2);

            foreach (explode(';', substr($cookie, $pos + 1)) as $attr) {
                $attr = trim($attr);
                if (($pos = strpos($attr, '=')) === false) {
                    $attr = strtolower($attr);
                    if ($attr === 'secure') {
                        $this->secure = true;
                    } elseif ($attr === 'httponly') {
                        $this->httponly = true;
                    }
                } else {
                    $name = strtolower(substr($attr, 0, $pos));
                    $value = substr($attr, $pos + 1);

                    if ($name === 'domain') {
                        $this->domain = $value;
                    } elseif ($name === 'path') {
                        $this->path = $value;
                    } elseif ($name === 'expires') {
                        $this->expires = strtotime($value);
                    }
                }
            }
        }
    }

    public function __toString()
    {
        return sprintf('%s=%s', $this->name, $this->value);
    }
}