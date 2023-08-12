<?php
declare(strict_types=1);

namespace ManaPHP\I18n;

use ManaPHP\Component;
use ManaPHP\Context\ContextCreatorInterface;

/**
 * @property-read \ManaPHP\I18n\LocaleContext $context
 */
class Locale extends Component implements LocaleInterface, ContextCreatorInterface
{
    protected string $default;

    public function __construct(string $default = 'en')
    {
        $this->default = $default;
    }

    public function createContext(): LocaleContext
    {
        /** @var \ManaPHP\I18n\LocaleContext $context */
        $context = $this->contextor->makeContext($this);

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