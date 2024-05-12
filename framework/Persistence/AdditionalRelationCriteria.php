<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

class AdditionalRelationCriteria
{
    protected array $fields = [];

    protected array $orders = [];

    public static function of(array $fields, array $orders = []): static
    {
        $instance = new static();

        $instance->fields = $fields;
        $instance->orders = $orders;

        return $instance;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getOrders(): array
    {
        return $this->orders;
    }
}