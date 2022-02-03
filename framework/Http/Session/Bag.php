<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\SessionInterface $session
 */
class Bag extends Component implements BagInterface
{
    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function destroy(): void
    {
        $this->session->remove($this->name);
    }

    public function set(string $property, mixed $value): static
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->name, $defaultCurrentValue);
        $data[$property] = $value;

        $this->session->set($this->name, $data);

        return $this;
    }

    public function get(?string $property = null, mixed $default = null): mixed
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->name, $defaultCurrentValue);

        if ($property === null) {
            return $data;
        } else {
            return $data[$property] ?? $default;
        }
    }

    public function has(string $property): bool
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->name, $defaultCurrentValue);

        return isset($data[$property]);
    }

    public function remove(string $property): void
    {
        $defaultCurrentValue = [];
        $data = $this->session->get($this->name, $defaultCurrentValue);
        unset($data[$property]);

        $this->session->set($this->name, $data);
    }
}