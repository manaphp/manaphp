<?php
declare(strict_types=1);

namespace ManaPHP;

use Stringable;

class Exception extends \Exception
{
    protected array $json = [];

    public function __construct(string|Stringable|array $message = '', int $code = 0, \Exception $previous = null)
    {
        if (is_array($message)) {
            $replaces = [];

            preg_match_all('#{(\w+)}#', $message[0], $matches);
            foreach ($matches[1] as $key) {
                if (($val = $message[$key] ?? null) !== null) {
                    if (is_bool($val)) {
                        $val = $val ? 'true' : 'false';
                    } elseif (is_object($val)) {
                        $val = $val instanceof Stringable ? (string)$val : json_stringify($val);
                    }

                    $replaces['{' . $key . '}'] = $val;
                }
            }

            $message = strtr($message[0], $replaces);
        } elseif ($message instanceof Stringable) {
            $message = (string)$message;
        }

        parent::__construct($message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return 500;
    }

    public function setJson(mixed $data): static
    {
        if (is_array($data)) {
            $this->json = $data;
        } elseif (is_string($data)) {
            $this->json = ['code' => 1, 'message' => $data];
        } elseif (is_int($data)) {
            $this->json = ['code' => $data, 'message' => $this->getMessage()];
        } else {
            $this->json = $data;
        }

        return $this;
    }

    public function getJson(): array
    {
        if ($this->json) {
            return $this->json;
        } else {
            $code = $this->getStatusCode();
            $message = $code === 500 ? 'Server Internal Error' : $this->getMessage();
            return ['code' => $code === 200 ? -1 : $code, 'message' => $message];
        }
    }
}
