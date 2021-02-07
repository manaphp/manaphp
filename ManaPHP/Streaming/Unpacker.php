<?php

namespace ManaPHP\Streaming;

class Unpacker
{
    /**
     * @var string
     */
    protected $format;

    /**
     * @param string $format
     * @param array  $names
     *
     * @return static
     */
    public function append($format, $names)
    {
        foreach ($names as $name) {
            if ($this->format !== null) {
                $this->format .= '/';
            }

            $this->format .= $format . $name;
        }

        return $this;
    }

    /**
     * string
     *
     * @param string $name
     *
     * @return static
     */
    public function string($name)
    {
        return $this->stringNP($name, '*');
    }

    /**
     * NUL-padded string
     *
     * @param string     $name
     * @param int|string $len
     *
     * @return static
     */
    public function stringNP($name, $len)
    {
        if ($this->format !== null) {
            $this->format .= '/';
        }
        $this->format .= 'a' . $name . $len;

        return $this;
    }

    /**
     * SPACE-padded string
     *
     * @param string     $name
     * @param int|string $len
     *
     * @return static
     */
    public function stringSP($name, $len)
    {
        if ($this->format !== null) {
            $this->format .= '/';
        }
        $this->format .= 'A' . $name . $len;

        return $this;
    }

    /**
     * Hex string, low nibble first
     *
     * @param string ...$args
     *
     * @return static
     */
    public function hexLNF(...$args)
    {
        return $this->append('h', $args);
    }

    /**
     * Hex string, high nibble first
     *
     * @param string ...$args
     *
     * @return static
     */
    public function hexHNF(...$args)
    {
        return $this->append('H', $args);
    }

    /**
     * unsigned char
     *
     * @param string ...$args
     *
     * @return static
     */
    public function byte(...$args)
    {
        return $this->append('C', $args);
    }

    /**
     * signed char
     *
     * @param string ...$args
     *
     * @return static
     */
    public function int8(...$args)
    {
        return $this->append('c', $args);
    }

    /**
     * unsigned char
     *
     * @param string ...$args
     *
     * @return static
     */
    public function int8U(...$args)
    {
        return $this->append('C', $args);
    }

    /**
     * signed short (always 16 bit, machine byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int16(...$args)
    {
        return $this->append('s', $args);
    }

    /**
     * unsigned short (always 16 bit, machine byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int16U(...$args)
    {
        return $this->append('S', $args);
    }

    /**
     * unsigned short (always 16 bit, big endian byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int16BE(...$args)
    {
        return $this->append('n', $args);
    }

    /**
     * unsigned short (always 16 bit, little endian byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int16LE(...$args)
    {
        return $this->append('v', $args);
    }

    /**
     * signed long (always 32 bit, machine byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int32(...$args)
    {
        return $this->append('l', $args);
    }

    /**
     * unsigned long (always 32 bit, machine byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int32U(...$args)
    {
        return $this->append('L', $args);
    }

    /**
     * unsigned long (always 32 bit, big endian byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int32BE(...$args)
    {
        return $this->append('N', $args);
    }

    /**
     * unsigned long (always 32 bit, little endian byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int32LE(...$args)
    {
        return $this->append('V', $args);
    }

    /**
     * signed long long (always 64 bit, machine byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int64(...$args)
    {
        return $this->append('q', $args);
    }

    /**
     * unsigned long long (always 64 bit, machine byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int64U(...$args)
    {
        return $this->append('Q', $args);
    }

    /**
     * unsigned long long (always 64 bit, big endian byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int64BE(...$args)
    {
        return $this->append('J', $args);
    }

    /**
     * unsigned long long (always 64 bit, little endian byte order)
     *
     * @param int ...$args
     *
     * @return static
     */
    public function int64LE(...$args)
    {
        return $this->append('P', $args);
    }

    /**
     * float (machine dependent size and representation)
     *
     * @param float ...$args
     *
     * @return static
     */
    public function float(...$args)
    {
        return $this->append('f', $args);
    }

    /**
     * float (machine dependent size, little endian byte order)
     *
     * @param float ...$args
     *
     * @return static
     */
    public function floatLE(...$args)
    {
        return $this->append('g', $args);
    }

    /**
     * float (machine dependent size, big endian byte order)
     *
     * @param float ...$args
     *
     * @return static
     */
    public function floatBE(...$args)
    {
        return $this->append('G', $args);
    }

    /**
     * double (machine dependent size and representation)
     *
     * @param double ...$args
     *
     * @return static
     */
    public function double(...$args)
    {
        return $this->append('G', $args);
    }

    /**
     * double (machine dependent size, little endian byte order)
     *
     * @param double ...$args
     *
     * @return static
     */
    public function doubleLE(...$args)
    {
        return $this->append('e', $args);
    }

    /**
     * double (machine dependent size, big endian byte order)
     *
     * @param double ...$args
     *
     * @return static
     */
    public function doubleBE(...$args)
    {
        return $this->append('E', $args);
    }

    /**
     * @return string
     */
    public function format()
    {
        return $this->format;
    }

    /**
     * @param string $str
     * @param int    $offset
     *
     * @return array|false
     */
    public function unpack($str, $offset = 0)
    {
        return unpack($this->format, $str, $offset);
    }
}
