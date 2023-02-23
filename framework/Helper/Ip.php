<?php
declare(strict_types=1);

namespace ManaPHP\Helper;

class Ip
{
    public static function contains(string|array $haystack, string $needle, bool $cidr_only = false): bool
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
                    $prefix = substr($item, 0, $pos);
                    $suffix = substr($item, $pos + 1);
                    $bits = str_pad(str_repeat('1', (int)$suffix), 32, '0');
                    $mask = (int)base_convert($bits, 2, 10);
                    if (($mask & $needle_int) === ($mask & ip2long($prefix))) {
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
                    $prefix = substr($item, 0, $pos);
                    $suffix = substr($item, $pos + 1);
                    if (str_contains($suffix, '.')/** 126.1.0.0/255.255.0.0 */) {
                        $mask = ip2long($suffix);
                        if (($mask & $needle_int) === ($mask & ip2long($prefix))) {
                            return true;
                        }
                    } else {
                        $bits = str_pad(str_repeat('1', (int)$suffix), 32, '0');
                        $mask = (int)base_convert($bits, 2, 10);
                        if (($mask & $needle_int) === ($mask & ip2long($prefix))/** 126.1.0.0/32 */) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    public static function local(): string
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
                    if ($name === 'docker' || str_starts_with($name, 'br-')) {
                        continue;
                    }

                    return $ip;
                }
                return current($ips);
            }
        } elseif (DIRECTORY_SEPARATOR === '\\' || PHP_OS === "Darwin") {
            return '127.0.0.1';
        } else {
            if (!$ips = @exec('hostname --all-ip-addresses')) {
                return '127.0.0.1';
            }

            $ips = explode(' ', $ips);

            foreach ($ips as $ip) {
                if (str_starts_with($ip, '172.') && preg_match('#\.1$#', $ip)) {
                    continue;
                }
                return $ip;
            }
            return $ips[0];
        }
    }
}