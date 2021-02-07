<?php

namespace ManaPHP\I18n;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

class TranslatorContext
{
    public $locale;
}

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\I18n\TranslatorContext $_context
 */
class Translator extends Component implements TranslatorInterface
{
    /**
     * @var string
     */
    protected $_locale;

    /**
     * @var string
     */
    protected $_dir = '@resources/Translator';

    /**
     * @var array
     */
    protected $_files = [];

    /**
     * @var array
     */
    protected $_templates;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['locale'])) {
            $this->_locale = $options['locale'];
        }

        if (isset($options['dir'])) {
            $this->_dir = $options['dir'];
        }

        foreach (LocalFS::glob($this->_dir . '/*.php') as $file) {
            $this->_files[strtolower(pathinfo($file, PATHINFO_FILENAME))] = $file;
        }
    }

    protected function createContext()
    {
        /** @var \ManaPHP\Validating\ValidatorContext $context */
        $context = parent::createContext();

        if ($this->_locale !== null) {
            $context->locale = $this->_locale;
        } elseif (!MANAPHP_CLI) {
            $locale = $this->configure->language;
            if (($language = strtolower($this->request->get('lang', ''))) && isset($this->_files[$language])) {
                $locale = $language;
            } elseif ($language = $this->request->getAcceptLanguage()) {
                if (preg_match_all('#[a-z\-]{2,}#', strtolower($language), $matches)) {
                    foreach ($matches[0] as $lang) {
                        if (isset($this->_files[$lang])) {
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
        $this->_context->locale = $locale;

        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->_context->locale;
    }

    /**
     * @param string $template
     * @param array  $placeholders
     *
     * @return string
     */
    public function translate($template, $placeholders = null)
    {
        $locale = $this->_locale ?: $this->_context->locale;

        if (!isset($this->_templates[$locale])) {
            /** @noinspection PhpIncludeInspection */
            $templates = require $this->_files[$locale];
            $this->_templates[$locale] = $templates;
        } else {
            $templates = $this->_templates[$locale];
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