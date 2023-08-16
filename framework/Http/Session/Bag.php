<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Http\SessionInterface;

class Bag extends Component implements BagInterface
{
    #[Inject] protected SessionInterface $session;

    #[Value] protected string $name;

    public function destroy(): void
    {
        $this->session->remove($this->name);
    }

    public function set(string $property, mixed $value): static
    {
        $data = $this->session->get($this->name, []);
        $data[$property] = $value;

        $this->session->set($this->name, $data);

        return $this;
    }

    public function all(): array
    {
        return $this->session->get($this->name, []);
    }

    public function get(string $property, mixed $default = null): mixed
    {
        $data = $this->session->get($this->name, []);

        return $data[$property] ?? $default;
    }

    public function has(string $property): bool
    {
        $data = $this->session->get($this->name, []);

        return isset($data[$property]);
    }

    public function remove(string $property): void
    {
        $data = $this->session->get($this->name, []);
        unset($data[$property]);

        $this->session->set($this->name, $data);
    }
}