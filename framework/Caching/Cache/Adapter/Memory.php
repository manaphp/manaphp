<?php
declare(strict_types=1);

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\AbstractCache;

/**
 * @property-read \ManaPHP\Caching\Cache\Adapter\MemoryContext $context
 */
class Memory extends AbstractCache
{
    public function do_get(string $key): false|string
    {
        $context = $this->context;

        if (isset($context->data[$key])) {
            if ($context->data[$key]['deadline'] >= time()) {
                return $context->data[$key]['data'];
            } else {
                unset($context->data[$key]);

                return false;
            }
        } else {
            return false;
        }
    }

    public function do_set(string $key, string $value, int $ttl): void
    {
        $context = $this->context;

        $context->data[$key] = ['deadline' => time() + $ttl, 'data' => $value];
    }

    public function do_delete(string $key): void
    {
        $context = $this->context;

        unset($context->data[$key]);
    }

    public function do_exists(string $key): bool
    {
        $context = $this->context;

        return isset($context->data[$key]) && $context->data[$key]['deadline'] >= time();
    }
}