<?php
declare(strict_types=1);

namespace ManaPHP\I18n;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\I18n\LocaleContext $context
 */
class Locale extends Component implements LocaleInterface
{
    protected string $default = 'en';

    public function __construct(array $options = [])
    {
        if (isset($options['default'])) {
            $this->default = $options['default'];
        }
    }

    protected function createContext(): LocaleContext
    {
        /** @var \ManaPHP\I18n\LocaleContext $context */
        $context = parent::createContext();

        $context->locale = $this->default;

        return $context;
    }

    public function get(): string
    {
        return $this->context->locale;
    }

    public function set(string $locale): static
    {
        $this->context->locale = $locale;

        return $this;
    }
}