<?php
namespace ManaPHP\I18n;

use ManaPHP\Component;

class TranslatorContext
{
    public $locale;
}

/**
 * Class ManaPHP\Message\Translator
 *
 * @package i18n
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property \ManaPHP\I18n\TranslatorContext     $_context
 */
class Translator extends Component implements TranslatorInterface
{
    /**
     * @var array
     */
    protected $_files = [];

    /**
     * @var array
     */
    protected $_messages;

    /**
     * @var array
     */
    protected $_locale;

    /**
     * translator constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['files'])) {
            $files = $options['files'];
            if (is_string($files)) {
                $files = preg_split('#[\s,]+#', $files, -1, PREG_SPLIT_NO_EMPTY);
            }
        } else {
            $files = ['@manaphp/messages', '@root/messages'];
        }
        $this->_files = $files;

        if (isset($options['locale'])) {
            $this->_locale = $options['locale'];
        }

        $this->eventsManager->attachEvent('request:construct', [$this, 'onConstruct']);
    }

    public function onConstruct()
    {
        $context = $this->_context;

        if ($this->_locale) {
            $context->locale = $this->_locale;
        } elseif ($accept_lang = $this->request->getServer('HTTP_ACCEPT_LANGUAGE')) {
            if (($pos = strpos($accept_lang, ',')) !== false) {
                $context->locale = substr($accept_lang, 0, $pos);
            } else {
                $context->locale = $accept_lang;
            }
        } else {
            $context->locale = 'en';
        }
    }

    /**
     * @param string $locale
     *
     * @return static
     */
    public function setLocale($locale)
    {
        $context = $this->_context;

        $context->locale = $locale;

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
        $context = $this->_context;

        $locale = $context->locale;
        if ($pos = strpos($locale, '-')) {
            $fallback = substr($locale, 0, $pos);
        } else {
            $fallback = null;
        }

        if ($this->_messages === null) {
            foreach ($this->_files as $file) {
                $messages = [];
                $file = $this->alias->resolve("$file/$locale.php");
                if (is_file($file)) {
                    /** @noinspection PhpIncludeInspection */
                    $messages = require $file;
                } elseif ($fallback) {
                    $file = $this->alias->resolve("$file/$fallback.php");
                    if (is_file($file)) {
                        /** @noinspection PhpIncludeInspection */
                        $messages = require $file;
                    }
                }

                if ($messages) {
                    if ($this->_messages) {
                        /** @noinspection SlowArrayOperationsInLoopInspection */
                        $this->_messages = array_merge($this->_messages, $messages);
                    } else {
                        $this->_messages = $messages;
                    }
                }
            }
        }

        $message = isset($this->_messages[$template]) ? $this->_messages[$template] : $template;

        if ($placeholders) {
            $replaces = [];

            if (strpos($message, ':') !== false) {
                foreach ($placeholders as $k => $v) {
                    $replaces[':' . $k] = $v;
                }
            }

            if (strpos($message, '{') !== false) {
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