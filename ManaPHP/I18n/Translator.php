<?php

namespace ManaPHP\I18n;

use ManaPHP\Component;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Helper\LocalFS;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class TranslatorContext
{
    public $locale;
}

/**
 * @property-read \ManaPHP\Configuration\Configure $configure
 * @property-read \ManaPHP\Http\RequestInterface   $request
 * @property-read \ManaPHP\I18n\TranslatorContext  $context
 */
class Translator extends Component implements TranslatorInterface
{
    /**
     * @var string
     */
    protected $locale;

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
        if (isset($options['locale'])) {
            $this->locale = $options['locale'];
        }

        if (isset($options['dir'])) {
            $this->dir = $options['dir'];
        }

        foreach (LocalFS::glob($this->dir . '/*.php') as $file) {
            $this->files[strtolower(pathinfo($file, PATHINFO_FILENAME))] = $file;
        }
    }

    protected function createContext()
    {
        /** @var \ManaPHP\Validating\ValidatorContext $context */
        $context = parent::createContext();

        if ($this->locale !== null) {
            $context->locale = $this->locale;
        } elseif (!MANAPHP_CLI) {
            $locale = $this->configure->language;
            if (($language = strtolower($this->request->get('lang', ''))) && isset($this->files[$language])) {
                $locale = $language;
            } elseif ($language = $this->request->getAcceptLanguage()) {
                if (preg_match_all('#[a-z\-]{2,}#', strtolower($language), $matches)) {
                    foreach ($matches[0] as $lang) {
                        if (isset($this->files[$lang])) {
                            $locale = $lang;
                            break;
                        }
                    }
                }
            }
            $context->locale = $locale;
        } else {
            $context->locale = $this->configure->language;
        }

        return $context;
    }

    /**
     * @param string $locale
     *
     * @return static
     */
    public function setLocale($locale)
    {
        $this->context->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->context->locale;
    }

    /**
     * @param string $template
     * @param array  $placeholders
     *
     * @return string
     */
    public function translate($template, $placeholders = null)
    {
        $locale = $this->locale ?: $this->context->locale;

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