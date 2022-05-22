<?php
declare(strict_types=1);

namespace ManaPHP\Streaming;

class Unpacker
{
    protected ?string $format = null;

    public function append(string $format, array $names): static
    {
        foreach ($names as $name) {
            if ($this->format !== null) {
                $this->format .= '/';
            }

            $this->format .= $format . $name;
        }

        return $this;
    }

    public function string(string $name): static
    {
        return $this->stringNP($name, '*');
    }

    public function stringNP(string $name, int|string $len): static
    {
        if ($this->format !== null) {
            $this->format .= '/';
        }
        $this->format .= 'a' . $name . $len;

        return $this;
    }

    public function stringSP(string $name, int|string $len): static
    {
        if ($this->format !== null) {
            $this->format .= '/';
        }
        $this->format .= 'A' . $name . $len;

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

    public function unpack(string $str, int $offset = 0): ?array
    {
        $v = unpack($this->format, $str, $offset);
        return $v === false ? null : $v;
    }
}