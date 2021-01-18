<?php

namespace ManaPHP\Streaming;

class Unpacker
{
    /**
     * @var string
     */
    protected $_format;

    /**
     * @param string $format
     * @param array  $names
     *
     * @return static
     */
    public function _append($format, $names)
    {
        foreach ($names as $name) {
            if ($this->_format !== null) {
                $this->_format .= '/';
            }

            $this->_format .= $format . $name;
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
    public function s($name)
    {
        return $this->sn($name, '*');
    }

    /**
     * NUL-padded string
     *
     * @param string     $name
     * @param int|string $len
     *
     * @return static
     */
    public function sn($name, $len)
    {
        if ($this->_format !== null) {
            $this->_format .= '/';
        }
        $this->_format .= 'a' . $name . $len;

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
    public function ss($name, $len)
    {
        if ($this->_format !== null) {
            $this->_format .= '/';
        }
        $this->_format .= 'A' . $name . $len;

        return $this;
    }

    /**
     * Hex string, low nibble first
     *
     * @param string ...$args
     *
     * @return static
     */
    public function hl(...$args)
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
    public function hh(...$args)
    {
        return $this->_append('H', $args);
    }

    /**
     * signed char
     *
     * @param string ...$args
     *
     * @return static
     */
    public function sc(...$args)
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
    public function uc(...$args)
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
    public function i16(...$args)
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
    public function u16(...$args)
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
    public function u16b(...$args)
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
    public function u16l(...$args)
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
    public function i32(...$args)
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
    public function u32(...$args)
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
    public function u32b(...$args)
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
    public function u32l(...$args)
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
    public function i64(...$args)
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
    public function u64(...$args)
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
    public function u64b(...$args)
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
    public function u64l(...$args)
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
    public function f(...$args)
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
    public function fl(...$args)
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
    public function fb(...$args)
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
    public function d(...$args)
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
    public function dl(...$args)
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
    public function db(...$args)
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
     * @param string $str
     * @param int    $offset
     *
     * @return array
     */
    public function unpack($str, $offset = 0)
    {
        return unpack($this->_format, $str, $offset);
    }
}
