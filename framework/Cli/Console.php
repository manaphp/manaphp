<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use JsonSerializable;
use ManaPHP\Di\Attribute\Inject;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use Stringable;
use Throwable;
use function getenv;

class Console implements ConsoleInterface
{
    #[Inject] protected LoggerInterface $logger;

    protected int $width = 80;

    public const FC_BLACK = 0x01;
    public const FC_RED = 0x02;
    public const FC_GREEN = 0x04;
    public const FC_YELLOW = 0x08;
    public const FC_BLUE = 0x10;
    public const FC_MAGENTA = 0x20;
    public const FC_CYAN = 0x40;
    public const FC_WHITE = 0x80;

    public const BC_BLACK = 0x0100;
    public const BC_RED = 0x0200;
    public const BC_GREEN = 0x0400;
    public const BC_YELLOW = 0x0800;
    public const BC_BLUE = 0x1000;
    public const BC_MAGENTA = 0x2000;
    public const BC_CYAN = 0x4000;
    public const BC_WHITE = 0x8000;

    public const AT_BOLD = 0x010000;
    public const AT_ITALICS = 0x020000;
    public const AT_UNDERLINE = 0x040000;
    public const AT_BLINK = 0x080000;
    public const AT_INVERSE = 0x100000;

    public function isSupportColor(): bool
    {
        if (DIRECTORY_SEPARATOR === '/') {
            return true;
        }

        return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI') || 'xterm' === getenv('TERM');
    }

    public function colorize(string $text, int $options = 0, int $width = 0): string
    {
        $map = [
            self::AT_BOLD      => "\033[1m",
            self::AT_ITALICS   => "\033[3m",
            self::AT_UNDERLINE => "\033[4m",
            self::AT_BLINK     => "\033[5m",
            self::AT_INVERSE   => "\033[7m",

            self::BC_BLACK   => "\033[40m",
            self::BC_RED     => "\033[41m",
            self::BC_GREEN   => "\033[42m",
            self::BC_YELLOW  => "\033[43m",
            self::BC_BLUE    => "\033[44m",
            self::BC_MAGENTA => "\033[45m",
            self::BC_CYAN    => "\033[46m",
            self::BC_WHITE   => "\033[47m",

            self::FC_BLACK   => "\033[30m",
            self::FC_RED     => "\033[31m",
            self::FC_GREEN   => "\033[32m",
            self::FC_YELLOW  => "\033[33m",
            self::FC_BLUE    => "\033[34m",
            self::FC_MAGENTA => "\033[35m",
            self::FC_CYAN    => "\033[36m",
            self::FC_WHITE   => "\033[37m",
        ];

        if (!$this->isSupportColor()) {
            return $width ? str_pad($text, $width) : $text;
        }

        $c = '';
        for ($i = 0; $i < 32; $i++) {
            if ((1 << $i) & $options) {
                $c .= $map[(1 << $i) & $options];
            }
        }

        return $c . $text . "\033[0m" . str_repeat(' ', max($width - strlen($text), 0));
    }

    protected function interpolateMessage(string $message, array $context): string
    {
        $replaces = [];
        preg_match_all('#{([\w.]+)}#', $message, $matches);
        foreach ($matches[1] as $key) {
            if (($val = $context[$key] ?? null) === null) {
                continue;
            }

            if (is_array($val) || is_scalar($val)) {
                $val = json_stringify($val);
            } elseif (is_object($val)) {
                if (method_exists($val, '__toString')) {
                    $val = (string)$val;
                } elseif ($val instanceof JsonSerializable) {
                    $val = json_stringify($val,);
                } else {
                    $val = json_stringify((array)$val);
                }
            } else {
                continue;
            }

            $replaces['{' . $key . '}'] = $val;
        }
        return strtr($message, $replaces);
    }

    public function write(string|Stringable $message, array $context = [], int $options = 0): void
    {
        if (is_string($message)) {
            if ($context !== [] && str_contains($message, '{')) {
                $message = $this->interpolateMessage($message, $context);
            }
        }

        if ($options === 0) {
            echo $message;
        } else {
            echo $this->colorize($message, $options);
        }

        if (($v = $context['exception'] ?? null) !== null && $v instanceof Throwable) {
            echo $message;
        }
    }

    public function sampleColorizer(): void
    {
        $rClass = new ReflectionClass($this);
        $bc_list = [0 => 0];
        $fc_list = [0 => 0];

        foreach ($rClass->getConstants() as $name => $value) {
            if (str_starts_with($name, 'BC_')) {
                $bc_list[$name] = $value;
            } elseif (str_starts_with($name, 'FC_')) {
                $fc_list[$name] = $value;
            }
        }

        foreach ($bc_list as $bc_name => $bc_value) {
            foreach ($fc_list as $fc_name => $fc_value) {
                $headers = [];
                if ($bc_value) {
                    $headers[] = 'Console::' . $bc_name;
                }

                if ($fc_value) {
                    $headers[] = 'Console::' . $fc_name;
                }

                $www = $this->colorize('ManaPHP https://www.manaphp.com/', $bc_value | $fc_value);
                echo str_pad(implode('|', $headers), 40), $www, PHP_EOL;
            }
        }

        $this->write('');
        $this->info('This is info text');
        $this->warning('This is warn text');
        $this->success('This is success text');
        sleep(1);
        $this->error('This is error text');

        $progress = 0;
        while ($progress < 100) {
            $progress += random_int(1, $progress > 10 ? 20 : 3);
            $this->progress('current process is {0}', $progress);
            sleep(1);
        }
    }

    public function writeLn(string|Stringable $message = '', array $context = [], int $options = 0): void
    {
        $this->write($message, $context, $options);
        $this->write(PHP_EOL);
    }

    public function debug(string|Stringable $message = '', array $context = [], int $options = 0): void
    {
        $this->logger->log(LogLevel::DEBUG, $message);
        $this->writeLn($message, $context, $options);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->logger->log(LogLevel::INFO, $message, $context);
        $this->writeLn($message, $context, self::FC_YELLOW);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->logger->log(LogLevel::WARNING, $message, $context);
        $this->writeLn($message, $context, self::FC_MAGENTA);
    }

    public function success(string|Stringable $message, array $context = []): void
    {
        $this->logger->log(LogLevel::INFO, $message, $context);
        $this->writeLn($message, $context, self::FC_BLUE);
    }

    public function error(string|Stringable $message, array $context = [], int $code = 1): int
    {
        $this->logger->log(LogLevel::ERROR, $message, $context);
        $this->writeLn($message, $context, self::FC_RED);

        return $code;
    }

    public function progress(string|Stringable $message, mixed $value = null): void
    {
        if ($value !== null) {
            if (is_int($value) || is_float($value)) {
                $percent = sprintf('%2.2f', $value) . '%';
            } else {
                $percent = $value;
            }

            $context = [$this->colorize($percent, self::FC_GREEN)];
        } else {
            $context = [0];
        }

        $this->write(str_pad("\r", $this->width));
        $this->write("\r");
        $this->write($message, $context);

        if ($value === null) {
            $this->write(PHP_EOL);
        }
    }

    public function read(): string
    {
        return trim(fgets(STDIN));
    }

    public function ask(string $message): string
    {
        if (str_ends_with($message, '?')) {
            $this->writeLn($message);
        } elseif (str_ends_with($message, ':')) {
            $this->write($message . ' ');
        } else {
            $this->write($message . ': ');
        }

        return $this->read();
    }
}