<?php
declare(strict_types=1);

namespace ManaPHP\Text;

use JsonSerializable;
use ManaPHP\Alias\Path;
use ManaPHP\Helper\SuppressWarnings;
use Stringable;
use Throwable;
use function dirname;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use function json_stringify;
use function preg_match_all;
use function preg_replace;
use function realpath;
use function str_contains;
use function strtr;

class InterpolatingFormatter implements InterpolatingFormatterInterface
{
    public function interpolate(string|Stringable $message, array $context): string
    {
        $replaces = [];
        preg_match_all('#{([\w.]+)}#', $message, $matches);
        foreach ($matches[1] as $key) {
            if (($val = $context[$key] ?? null) === null) {
                continue;
            }

            if (is_string($val)) {
                SuppressWarnings::noop();
            } elseif ($val instanceof JsonSerializable) {
                $val = json_stringify($val);
            } elseif ($val instanceof Stringable) {
                $val = (string)$val;
            } elseif (is_scalar($val)) {
                $val = json_stringify($val);
            } elseif (is_array($val)) {
                $val = json_stringify($val);
            } elseif (is_object($val)) {
                $val = json_stringify((array)$val);
            } else {
                continue;
            }

            $replaces['{' . $key . '}'] = $val;
        }
        return strtr($message, $replaces);
    }

    public function format(string|Stringable $message, array $context): string
    {
        if (is_string($message)) {
            if ($context !== [] && str_contains($message, '{')) {
                $message = $this->interpolate($message, $context);
            }

            if (($exception = $context['exception'] ?? null) !== null && $exception instanceof Throwable) {
                $message .= ': ' . $this->exceptionToString($exception);
            }
            return $message;
        } elseif ($message instanceof Throwable) {
            return $this->exceptionToString($message);
        } else {
            return (string)$message;
        }
    }

    public function exceptionToString(Throwable $exception): string
    {
        $str = $exception::class . ': ' . $exception->getMessage() . PHP_EOL;
        $str .= '    at ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL;
        $traces = $exception->getTraceAsString();
        $str .= preg_replace('/#\d+\s/', '    at ', $traces);

        $prev = $traces;
        $caused = $exception;
        while ($caused = $caused->getPrevious()) {
            $str .= PHP_EOL . '  Caused by ' . $caused::class . ': ' . $caused->getMessage() . PHP_EOL;
            $str .= '    at ' . $caused->getFile() . ':' . $caused->getLine() . PHP_EOL;
            $traces = $exception->getTraceAsString();
            if ($traces !== $prev) {
                $str .= preg_replace('/#\d+\s/', '    at ', $traces);
            } else {
                $str .= '    at ...';
            }

            $prev = $traces;
        }

        $replaces = [];
        $replaces[dirname(realpath(Path::resolve('@root'))) . DIRECTORY_SEPARATOR] = '';

        return strtr($str, $replaces);
    }
}