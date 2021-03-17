<?php /** @noinspection PhpMultipleClassesDeclarationsInOneFile */

namespace ManaPHP\I18n;

use ManaPHP\Component;

class LocaleContext
{
    /**
     * @var string
     */
    public $locale;
}

/**
 * @property-read \ManaPHP\I18n\LocaleContext $context
 */
class Locale extends Component implements LocaleInterface
{
    /**
     * @var string
     */
    protected $default = 'en';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['default'])) {
            $this->default = $options['default'];
        }
    }

    protected function createContext()
    {
        /** @var \ManaPHP\I18n\LocaleContext $context */
        $context = parent::createContext();

        $context->locale = $this->default;

        return $context;
    }

    /**
     * @return string
     */
    public function get()
    {
        return $this->context->locale;
    }

    /**
     * @param string $locale
     *
     * @return static
     */
    public function set($locale)
    {
        $this->context->locale = $locale;

        return $this;
    }
}