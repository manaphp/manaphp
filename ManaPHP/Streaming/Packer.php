<?php
declare(strict_types=1);

namespace ManaPHP\Streaming;

class Packer
{
    protected string $format = '';
    protected array $values = [];

    protected function append(string $format, array $args): static
    {
        $count = count($args);
        if ($count === 1) {
            $this->format .= $format;
            $this->values[] = $args[0];
        } else {
            $this->format .= $format . count($args);
            $this->values = array_merge($this->values, $args);
        }

        return $this;
    }

    public function string(...$args): static
    {
        foreach ($args as $arg) {
            $this->format .= 'a' . strlen($arg);
            $this->values[] = $arg;
        }

        return $this;
    }

    public function stringNP(string $str, int $len): static
    {
        $this->format .= 'a' . $len;
        $this->values[] = $str;

        return $this;
    }

    public function stringSP(string $str, int $len): static
    {
        $this->format .= 'A' . $len;
        $this->values[] = $str;

        return $this;
    }

    public function hexLNF(...$args): static
    {
        return $this->append('h', $args);
    }

    public function hexHNF(...$args): static
    {
        return $this->append('H', $args);
    }

    public function byte(...$args): static
    {
        return $this->append('C', $args);
    }

    public function int8(...$args): static
    {
        return $this->append('c', $args);
    }

    public function int8U(...$args): static
    {
        return $this->append('C', $args);
    }

    public function int16(...$args): static
    {
        return $this->append('s', $args);
    }

    public function int16U(...$args): static
    {
        return $this->append('S', $args);
    }

    public function int16BE(...$args): static
    {
        return $this->append('n', $args);
    }

    public function int16LE(...$args): static
    {
        return $this->append('v', $args);
    }

    public function int32(...$args): static
    {
        return $this->append('l', $args);
    }

    public function int32U(...$args): static
    {
        return $this->append('L', $args);
    }

    public function int32BE(...$args): static
    {
        return $this->append('N', $args);
    }

    public function int32LE(...$args): static
    {
        return $this->append('V', $args);
    }

    public function int64(...$args): static
    {
        return $this->append('q', $args);
    }

    public function int64U(...$args): static
    {
        return $this->append('Q', $args);
    }

    public function int64BE(...$args): static
    {
        return $this->append('J', $args);
    }

    public function int64LE(...$args): static
    {
        return $this->append('P', $args);
    }

    public function float(...$args): static
    {
        return $this->append('f', $args);
    }

    public function floatLE(...$args): static
    {
        return $this->append('g', $args);
    }

    public function floatBE(...$args): static
    {
        return $this->append('G', $args);
    }

    public function double(...$args): static
    {
        return $this->append('G', $args);
    }

    public function doubleLE(...$args): static
    {
        return $this->append('e', $args);
    }

    public function doubleBE(...$args): static
    {
        return $this->append('E', $args);
    }

    public function format(): string
    {
        return $this->format;
    }

    public function values(): array
    {
        return $this->values;
    }

    public function pack(): string
    {
        return pack($this->format, ...$this->values);
    }

    public function dump(): string
    {
        return bin2hex($this->pack());
    }
}