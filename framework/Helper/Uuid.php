<?php
declare(strict_types=1);

namespace ManaPHP\Helper;

use ManaPHP\Exception\InvalidValueException;

class Uuid
{
    /**
     * Generates a v4 random UUID (Universally Unique IDentifier)
     *
     * The version 4 UUID is purely random (except the version). It doesn't contain meaningful
     * information such as MAC address, time, etc. See RFC 4122 for details of UUID.
     *
     * This algorithm sets the version number (4 bits) as well as two reserved bits.
     * All other bits (the remaining 122 bits) are set using a random or pseudo-random data source.
     * Version 4 UUIDs have the form xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx where x is any hexadecimal
     * digit and y is one of 8, 9, A, or B (e.g., f47ac10b-58cc-4372-a567-0e02b2c3d479).
     *
     * @link https://www.ietf.org/rfc/rfc4122.txt
     *v
     * @return string
     */
    public static function v4(): string
    {
        $bytes = unpack('N1a/n1b/n1c/n1d/n1e/N1f', random_bytes(16));
        return sprintf(
            '%08x-%04x-%04x-%04x-%04x%08x',
            $bytes['a'], $bytes['b'], ($bytes['c'] & 0x0FFF) | 0x4000, ($bytes['d'] & 0x3FFF) | 0x8000, $bytes['e'],
            $bytes['f']
        );
    }

    public static function encode_int32(int $n): string
    {
        $bytes = unpack('n1a/n1g/n1b/n1c/n1d/n1e/N1f', random_bytes(16));
        $bytes['b'] = (~$bytes['a'] ^ ($n >> 16)) & 0xFFFF;
        $bytes['c'] = (~$bytes['g'] ^ $n) & 0xFFFF;

        return sprintf(
            '%04X%04X-%04X-%04X-%04X-%04X%08X', $bytes['a'], $bytes['g'], $bytes['b'], $bytes['c'], $bytes['d'],
            $bytes['e'], $bytes['f']
        );
    }

    public static function decode_int32(string $uuid): int
    {
        $xd = '[\da-fA-F]';
        if (!preg_match("#($xd{4})($xd{4})-($xd{4})-($xd{4})-($xd{4})-($xd{12})#", $uuid, $match)) {
            throw new InvalidValueException('uuid is not correct format');
        }

        $xd1 = hexdec($match[1]);
        $xd2 = hexdec($match[2]);
        $xd3 = hexdec($match[3]);
        $xd4 = hexdec($match[4]);
        return ((~$xd1 ^ $xd3) & 0xFFFF) << 16 | ((~$xd2 ^ $xd4) & 0xFFFF);
    }
}