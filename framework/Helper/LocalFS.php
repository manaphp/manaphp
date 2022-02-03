<?php
declare(strict_types=1);

namespace ManaPHP\Helper;

use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\AliasInterface;

class LocalFS
{
    public static AliasInterface $alias;

    public static function fileExists(string $file): bool
    {
        return is_file(self::$alias->resolve($file));
    }

    public static function fileSize(string $file): false|int
    {
        return @filesize(self::$alias->resolve($file));
    }

    public static function fileDelete(string $file): void
    {
        if (str_contains($file, '*')) {
            foreach (self::files($file) as $f) {
                if (!unlink($f) && self::fileExists($f)) {
                    $error = error_get_last()['message'] ?? '';
                    throw new RuntimeException(['delete `%s` failed: %s', $f, $error]);
                }
            }
        } else {
            $file = self::$alias->resolve($file);

            if (!unlink($file) && self::fileExists($file)) {
                $error = error_get_last()['message'] ?? '';
                throw new RuntimeException(['delete `%s` failed: %s', $file, $error]);
            }
        }
    }

    protected static function dirCreateInternal(string $dir, int $mode = 0755): void
    {
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }
    }

    public static function fileGet(string $file): string
    {
        if (($r = @file_get_contents(self::$alias->resolve($file))) === false) {
            throw new FileNotFoundException($file);
        }

        return $r;
    }

    public static function filePut(string $file, string $data): void
    {
        $file = self::$alias->resolve($file);

        self::dirCreateInternal(dirname($file));
        if (file_put_contents($file, $data, LOCK_EX) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['write `%s` file failed: %s', $file, $error]);
        }
    }

    public static function fileAppend(string $file, string $data): void
    {
        $file = self::$alias->resolve($file);
        self::dirCreateInternal(dirname($file));

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['write `%s` file failed: %s', $file, $error]);
        }
    }

    public static function fileMove(string $src, string $dst, bool $overwrite = false): void
    {
        $src = self::$alias->resolve($src);
        $dst = self::$alias->resolve($dst);

        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        if (!$overwrite && is_file($dst)) {
            throw new RuntimeException(['move `%s` to `%s` failed: file exists already', $src, $dst]);
        }

        if (!is_dir($dir = dirname($dst))) {
            self::dirCreateInternal($dir);
        }

        if (!rename($src, $dst)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['move `%s` to `%s` failed: %s', $src, $dst, $error]);
        }
    }

    public static function fileCopy(string $src, string $dst, bool $overwrite = false): void
    {
        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        $src = self::$alias->resolve($src);
        $dst = self::$alias->resolve($dst);

        if ($overwrite || !is_file($dst)) {
            self::dirCreateInternal(dirname($dst));

            if (!copy($src, $dst)) {
                $error = error_get_last()['message'] ?? '';
                throw new RuntimeException(['move `%s` to `%s` failed: %s', $src, $dst, $error]);
            }
        }
    }

    public static function dirExists(string $dir): bool
    {
        return is_dir(self::$alias->resolve($dir));
    }

    protected static function dirDeleteInternal(string $dir): void
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_file($path)) {
                if (!unlink($path)) {
                    $error = error_get_last()['message'] ?? '';
                    throw new RuntimeException(['delete `%s` file failed: %s', $path, $error]);
                }
            } elseif (is_dir($path)) {
                self::dirDeleteInternal($path);
            } else {
                break;
            }
        }

        if (!rmdir($dir)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['delete `%s` directory failed: %s', $dir, $error]);
        }
    }

    public static function dirDelete(string $dir): void
    {
        $dir = self::$alias->resolve($dir);

        if (!is_dir($dir)) {
            return;
        }

        self::dirDeleteInternal($dir);
    }

    public static function dirCreate(string $dir, int $mode = 0755): void
    {
        self::dirCreateInternal(self::$alias->resolve($dir), $mode);
    }

    public static function dirReCreate(string $dir, int $mode = 0755): void
    {
        self::dirDelete($dir);

        self::dirCreate($dir, $mode);
    }

    public static function dirMove(string $src, string $dst, bool $overwrite = false): void
    {
        $src = self::$alias->resolve($src);
        $dst = self::$alias->resolve($dst);

        if (!$overwrite && is_dir($dst)) {
            throw new RuntimeException(['move `%s` to `%s` failed: directory is exists already', $src, $dst]);
        }

        if (!is_dir($dir = dirname($dst))) {
            self::dirCreateInternal($dir);
        }

        if (!rename($src, $dst)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['move `%s` directory to `%s` directory failed: %s', $src, $dst, $error]);
        }
    }

    protected static function dirCopyInternal(string $src, string $dst, bool $overwrite): void
    {
        foreach (scandir($src, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            if (is_file($srcPath)) {
                if (($overwrite || !file_exists($dstPath)) && !copy($srcPath, $dstPath)) {
                    $error = error_get_last()['message'] ?? '';
                    throw new RuntimeException(['copy `%s` file to `%s` file failed: %s', $srcPath, $dstPath, $error]);
                }
            } elseif (is_dir($srcPath)) {
                if ($overwrite || !is_dir($dstPath)) {
                    self::dirCreateInternal($dstPath);
                    self::dirCopyInternal($srcPath, $dstPath, $overwrite);
                }
            } else {
                break;
            }
        }
    }

    public static function dirCopy(string $src, string $dst, bool $overwrite = false): void
    {
        $src = self::$alias->resolve($src);
        $dst = self::$alias->resolve($dst);

        if (!is_dir($src)) {
            throw new RuntimeException(['copy `%s` to `%s` failed: source directory is not exists', $src, $dst]);
        }
        self::dirCreateInternal($dst);
        self::dirCopyInternal($src, $dst, $overwrite);
    }

    public static function glob(string $pattern, int $flags = 0): array
    {
        $pattern = self::$alias->resolve($pattern);

        if (str_starts_with($pattern, 'phar://')) {
            $dir = dirname($pattern);

            if (!self::dirExists($dir)) {
                return [];
            }

            $r = [];
            $p = basename($pattern);
            $h = opendir($dir);
            while (($file = readdir($h)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                if (!fnmatch($p, $file)) {
                    continue;
                }

                if (($flags & GLOB_ONLYDIR) && !is_dir($dir . '/' . $file)) {
                    continue;
                }

                $r[] = $dir . '/' . $file;
            }
        } else {
            $r = glob($pattern, $flags);
            $r = $r !== false ? $r : [];
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach ($r as $k => $v) {
                $r[$k] = strtr($v, '\\', '/');
            }
        }

        return $r;
    }

    public static function scandir(string $dir, int $sorting_order = SCANDIR_SORT_ASCENDING): array
    {
        $r = @scandir(self::$alias->resolve($dir), $sorting_order);
        if ($r === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['scandir `%s` directory failed: %s', $dir, $error]);
        }

        $items = [];
        foreach ($r as $item) {
            if ($item !== '.' && $item !== '..') {
                $items[] = $item;
            }
        }

        return $items;
    }

    public static function files(string $dir): array
    {
        $dir = self::$alias->resolve($dir);

        $files = [];
        foreach (self::glob($dir . (str_contains($dir, '*') ? '' : '/*'), SCANDIR_SORT_ASCENDING) as $item) {
            if (is_file($item)) {
                $files[] = $item;
            }
        }

        return $files;
    }

    public static function directories(string $dir): array
    {
        return self::glob($dir . (str_contains($dir, '*') ? '' : '/*'), GLOB_ONLYDIR);
    }

    public static function getModifiedTime(string $path): false|int
    {
        return filemtime(self::$alias->resolve($path));
    }

    public static function chmod(string $file, int $mode): void
    {
        if (!chmod(self::$alias->resolve($file), $mode)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['chmod `%s` file to `%s` mode failed: %s', $file, $mode, $error]);
        }
    }
}

LocalFS::$alias = container(AliasInterface::class);