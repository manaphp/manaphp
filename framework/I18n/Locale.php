<?php
declare(strict_types=1);

namespace ManaPHP\I18n;

use ManaPHP\Context\ContextCreatorInterface;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Autowired;

class Locale implements LocaleInterface, ContextCreatorInterface
{
    use ContextTrait;

    #[Autowired] protected string $default = 'en';

    public function createContext(): LocaleContext
    {
        /** @var LocaleContext $context */
        $context = $this->contextor->makeContext($this);

        $context->locale = $this->default;

        return $context;
    }

    public function get(): string
    {
        /** @var LocaleContext $context */
        $context = $this->getContext();

        return $context->locale;
    }

    public function set(string $locale): static
    {
        /** @var LocaleContext $context */
        $context = $this->getContext();

        $context->locale = $locale;

        return $this;
    }
}