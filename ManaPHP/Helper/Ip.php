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
            if ($needle === $haystack || $haystack === '*') {
                return true;
            } elseif ($haystack === '') {
                return false;
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
                    if (str_contains($mask, '.')/** 126.1.0.0/255.255.0.0 */) {
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

    /**
     * @return string
     */
    public static function local()
    {
        if (function_exists('swoole_get_local_ip')) {
            $ips = swoole_get_local_ip();
            if (!$ips) {
                return '127.0.0.1';
            } elseif (isset($ips['eth0'])) {
                return $ips['eth0'];
            } elseif (isset($ips['ens33'])) {
                return $ips['ens33'];
            } elseif (isset($ips['ens1'])) {
                return $ips['ens1'];
            } else {
                foreach ($ips as $name => $ip) {
                    if ($name === 'docker' || strpos($name, 'br-') === 0) {
                        continue;
                    }

                    return $ip;
                }
                return current($ips);
            }
        } elseif (DIRECTORY_SEPARATOR === '\\') {
            return '127.0.0.1';
        } else {
            if (!$ips = @exec('hostname --all-ip-addresses')) {
                return '127.0.0.1';
            }

            $ips = explode(' ', $ips);

            foreach ($ips as $ip) {
                if (strpos($ip, '172.') === 0 && preg_match('#\.1$#', $ip)) {
                    continue;
                }
                return $ip;
            }
            return $ips[0];
        }
    }
}