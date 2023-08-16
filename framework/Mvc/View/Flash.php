<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View;

use ManaPHP\Component;
use ManaPHP\Context\ContextTrait;
use ManaPHP\Di\Attribute\Value;

class Flash extends Component implements FlashInterface
{
    use ContextTrait;

    #[Value] protected array $css
        = [
            'error'   => 'flash-error',
            'notice'  => 'flash-notice',
            'success' => 'flash-success',
            'warning' => 'flash-warning'
        ];

    public function error(string $message): void
    {
        $this->message('error', $message);
    }

    public function notice(string $message): void
    {
        $this->message('notice', $message);
    }

    public function success(string $message): void
    {
        $this->message('notice', $message);
    }

    public function warning(string $message): void
    {
        $this->message('warning', $message);
    }

    public function output(bool $remove = true): void
    {
        /** @var FlashContext $context */
        $context = $this->getContext();

        foreach ($context->messages as $message) {
            echo $message;
        }

        if ($remove) {
            $context->messages = [];
        }
    }

    protected function message(string $type, string $message): void
    {
        /** @var FlashContext $context */
        $context = $this->getContext();

        $css = $this->css[$type] ?? '';

        $context->messages[] = '<div class="' . $css . '">' . $message . '</div>' . PHP_EOL;
    }
}