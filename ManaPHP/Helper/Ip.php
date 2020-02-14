<?php
namespace ManaPHP\Helper;

class Ip
{
    /**
     * @param string|array $haystack
     * @param string       $needle
     * @param bool         $cidr_only
     *
     * @return bool
     */
    public static function contains($haystack, $needle, $cidr_only = false)
    {
        if (is_string($haystack)) {
            if ($needle === $haystack) {
                return true;
            }
            $haystack = preg_split('#[,\s]+#', $haystack, -1, PREG_SPLIT_NO_EMPTY);
        }

        $needle_int = ip2long($needle);

        if ($cidr_only) {
            foreach ($haystack as $item) {
                if (($pos = strpos($item, '/')) !== false) {
                    $net = substr($item, 0, $pos);
                    $mask = substr($item, $pos + 1);

                    $mask = ~((1 << (32 - $mask)) - 1);
                    if (($needle_int & $mask) === ip2long($net)) {
                        return true;
                    }
                } elseif ($item === $needle) {
                    return true;
                }
            }
        } else {
            foreach ($haystack as $item) {
                if ($item === $needle) {
                    return true;
                } elseif (($pos = strpos($item, '*')) !== false) {
                    if (strncmp($item, $needle, $pos) === 0) {
                        return true;
                    }
                } elseif (($pos = strpos($item, '-')) !== false/** 125.0.0.1-125.0.0.9 */) {
                    $start = substr($item, 0, $pos);
                    $end = substr($item, $pos + 1);
                    if ($needle_int >= ip2long($start) && $needle_int <= ip2long($end)) {
                        return true;
                    }
                } elseif (($pos = strpos($item, '/')) !== false) {
                    $net = substr($item, 0, $pos);
                    $mask = substr($item, $pos + 1);
                    if (strpos($mask, '.') !== false/** 126.1.0.0/255.255.0.0 */) {
                        if (($needle_int & ip2long($mask)) === ip2long($net)) {
                            return true;
                        }
                    } else {
                        $mask = ~((1 << (32 - $mask)) - 1);
                        if (($needle_int & $mask) === ip2long($net)/** 126.1.0.0/32 */) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}