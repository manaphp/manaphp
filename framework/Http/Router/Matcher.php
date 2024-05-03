<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\Helper\Str;
use function preg_replace_callback;
use function str_contains;

class Matcher implements MatcherInterface
{
    protected string $handler;
    protected array $params;

    public function __construct(string $handler, array $params = [])
    {
        if (str_contains($handler, '{')) {
            $handler = preg_replace_callback('#{([^}]+)}#', static function ($match) use (&$params) {
                $name = $match[1];
                $value = $params[$name];

                unset($params[$name]);
                return $name === 'action' ? Str::camelize($value) : Str::pascalize($value);
            }, $handler);
        }

        $this->handler = $handler;
        $this->params = $params;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}