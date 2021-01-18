<?php

namespace ManaPHP\Streaming;

class Packer
{
    /**
     * @var string
     */
    protected $_format = '';

    /**
     * @var array
     */
    protected $_values = [];

    /**
     * @param string $format
     * @param array  $args
     *
     * @return static
     */
    protected function _append($format, $args)
    {
        $count = count($args);
        if ($count === 1) {
            $this->_format .= $format;
            $this->_values[] = $args[0];
        } else {
            $this->_format .= $format . count($args);
            $this->_values = array_merge($this->_values, $args);
        }

        return $this;
    }

    /**
     * string
     *
     * @param string ...$args
     *
     * @return static
     */
    public function string(...$args)
    {
        foreach ($args as $arg) {
            $this->_format .= 'a' . strlen($arg);
            $this->_values[] = $arg;
        }

        return $this;
    }

    /**
     * NUL-padded string
     *
     * @param string $str
     * @param int    $len
     *
     * @return static
     */
    public function stringNP($str, $len)
    {
        $this->_format .= 'a' . $len;
        $this->_values[] = $str;

        return $this;
    }

    /**
     * SPACE-padded string
     *
     * @param string $str
     * @param int    $len
     *
     * @return static
     */
    public function stringSP($str, $len)
    {
        $this->_format .= 'A' . $len;
        $this->_values[] = $str;

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
        return $this->_append('h', $args);
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
        return $this->_append('H', $args);
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
        return $this->_append('C', $args);
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
        return $this->_append('c', $args);
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
        return $this->_append('C', $args);
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
        return $this->_append('s', $args);
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
        return $this->_append('S', $args);
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
        return $this->_append('n', $args);
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
        return $this->_append('v', $args);
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
        return $this->_append('l', $args);
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
        return $this->_append('L', $args);
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
        return $this->_append('N', $args);
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
        return $this->_append('V', $args);
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
        return $this->_append('q', $args);
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
        return $this->_append('Q', $args);
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
        return $this->_append('J', $args);
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
        return $this->_append('P', $args);
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
        return $this->_append('f', $args);
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
        return $this->_append('g', $args);
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
        return $this->_append('G', $args);
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
        return $this->_append('G', $args);
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
        return $this->_append('e', $args);
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
        return $this->_append('E', $args);
    }

    /**
     * @return string
     */
    public function format()
    {
        return $this->_format;
    }

    /**
     * @return array
     */
    public function values()
    {
        return $this->_values;
    }

    /**
     * @return string
     */
    public function pack()
    {
        return pack($this->_format, ...$this->_values);
    }

    /**
     * @return string
     */
    public function dump()
    {
        return bin2hex($this->pack());
    }
}