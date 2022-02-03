<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Event\Listener;

class Middleware extends Listener
{
    protected bool $enabled = true;

    public function __construct(array $options = [])
    {
        if (isset($options['enabled'])) {
            $this->enabled = $options['enabled'];
        }
    }

    public function categorizeLog(): string
    {
        return basename(str_replace('\\', '.', static::class), 'Middleware');
    }

    public function listen(): void
    {
        if ($this->enabled) {
            foreach (get_class_methods($this) as $method) {
                if (str_starts_with($method, 'on')) {
                    $event = lcfirst(substr($method, 2));
                    $this->attachEvent("request:$event", [$this, $method]);
                }
            }
        }
    }
}