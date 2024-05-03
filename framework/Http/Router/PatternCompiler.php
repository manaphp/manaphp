<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\Di\Attribute\Autowired;
use function explode;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function str_contains;
use function str_replace;

class PatternCompiler implements PatternCompilerInterface
{
    #[Autowired] protected bool $case_sensitive = true;
    #[Autowired] protected array $snippets
        = [
            '{controller}' => '{controller:var}',
            '{action}'     => '{action:var}',
            '{id}'         => '{id:\d+}',
            ':var}'        => ':[a-zA-Z][-\w]*[^_-]}',
            ':int}'        => ':\d+}',
            ':uuid}'       => ':[A-Fa-f0-9]{8}(-[A-Fa-f0-9]{4}){3}-[A-Fa-f0-9]{12}}',
        ];

    public function __construct()
    {
        foreach ($this->snippets as $key => $value) {
            if (preg_match('#:\w+}#', $value, $match) === 1) {
                $var = $match[0];
                if (($val = $this->snippets[$var] ?? null) !== null) {
                    $this->snippets[$key] = str_replace($var, $val, $value);
                }
            }
        }
    }

    public function compile(string $pattern): string
    {
        if (!str_contains($pattern, '{')) {
            return $pattern;
        }

        $pattern = strtr($pattern, $this->snippets);

        $need_restore_token = false;

        if (preg_match('#{\d#', $pattern) === 1) {
            $need_restore_token = true;
            $pattern = (string)preg_replace('#{([\d,]+)}#', '@\1@', $pattern);
        }

        $matches = [];
        if (preg_match_all('#{([A-Z].*)}#Ui', $pattern, $matches, PREG_SET_ORDER) > 0) {
            foreach ($matches as $match) {
                $parts = explode(':', $match[1], 2);
                $to = '(?<' . $parts[0] . '>' . ($parts[1] ?? '[^/]+') . ')';
                $pattern = (string)str_replace($match[0], $to, $pattern);
            }
        }

        if ($need_restore_token) {
            $pattern = (string)preg_replace('#@([\d,]+)@#', '{\1}', $pattern);
        }

        return '#^' . $pattern . '$#' . ($this->case_sensitive ? '' : 'i');
    }
}