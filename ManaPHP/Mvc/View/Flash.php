<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Mvc\View\FlashContext $context
 */
class Flash extends Component implements FlashInterface
{
    protected array $css;

    public function __construct(array $css = [])
    {
        $this->css = $css
            ?: [
                'error'   => 'flash-error',
                'notice'  => 'flash-notice',
                'success' => 'flash-success',
                'warning' => 'flash-warning'
            ];
    }

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
        $context = $this->context;

        foreach ($context->messages as $message) {
            echo $message;
        }

        if ($remove) {
            $context->messages = [];
        }
    }

    protected function message(string $type, string $message): void
    {
        $context = $this->context;

        $css = $this->css[$type] ?? '';

        $context->messages[] = '<div class="' . $css . '">' . $message . '</div>' . PHP_EOL;
    }
}