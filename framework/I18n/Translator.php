<?php
declare(strict_types=1);

namespace ManaPHP\I18n;

use ManaPHP\Component;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\I18n\LocaleInterface  $locale
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Translator extends Component implements TranslatorInterface
{
    protected string $dir;

    protected array $files = [];
    protected array $templates = [];

    public function __construct(string $dir = '@resources/Translator')
    {
        $this->dir = $dir;

        foreach (LocalFS::glob($this->dir . '/*.php') as $file) {
            $this->files[strtolower(pathinfo($file, PATHINFO_FILENAME))] = $file;
        }
    }

    public function translate(string $template, array $placeholders = []): string
    {
        $locale = $this->locale->get();

        if (!isset($this->templates[$locale])) {
            if (($file = $this->files[$locale] ?? null) === null) {
                throw new RuntimeException(['`%s` locale file is not exists', $locale]);
            }

            $templates = require $file;
            $this->templates[$locale] = $templates;
        } else {
            $templates = $this->templates[$locale];
        }

        $message = $templates[$template] ?? $template;

        if ($placeholders) {
            $replaces = [];

            if (str_contains($message, ':')) {
                foreach ($placeholders as $k => $v) {
                    $replaces[':' . $k] = $v;
                }
            }

            if (str_contains($message, '{')) {
                foreach ($placeholders as $k => $v) {
                    $replaces['{' . $k . '}'] = $v;
                }
            }

            return strtr($message, $replaces);
        } else {
            return $message;
        }
    }
}