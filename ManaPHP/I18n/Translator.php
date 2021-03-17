<?php

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
    /**
     * @var string
     */
    protected $dir = '@resources/Translator';

    /**
     * @var array
     */
    protected $files = [];

    /**
     * @var array
     */
    protected $templates;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['dir'])) {
            $this->dir = $options['dir'];
        }

        foreach (LocalFS::glob($this->dir . '/*.php') as $file) {
            $this->files[strtolower(pathinfo($file, PATHINFO_FILENAME))] = $file;
        }
    }

    /**
     * @param string $template
     * @param array  $placeholders
     *
     * @return string
     */
    public function translate($template, $placeholders = null)
    {
        $locale = $this->locale->get();

        if (!isset($this->templates[$locale])) {
            if (($file = $this->files[$locale] ?? null) === null) {
                throw new RuntimeException(['`%s` locale file is not exists', $locale]);
            }

            /** @noinspection PhpIncludeInspection */
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