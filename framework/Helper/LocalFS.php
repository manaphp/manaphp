<?php

declare(strict_types=1);

namespace ManaPHP\Helper;

use ManaPHP\Alias\Path;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\RuntimeException;
use function basename;
use function chmod;
use function copy;
use function dirname;
use function error_get_last;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function filesize;
use function fnmatch;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function opendir;
use function readdir;
use function rename;
use function rmdir;
use function rtrim;
use function scandir;
use function str_contains;
use function str_starts_with;
use function strtr;
use function unlink;

class LocalFS
{
    public static function fileExists(string|Path $file): bool
    {
        return is_file(Path::resolve($file));
    }

    public static function fileSize(string|Path $file): ?int
    {
        $v = @filesize(Path::resolve($file));

        return $v === false ? null : $v;
    }

    public static function fileDelete(string|Path $file): void
    {
        if (str_contains($file, '*')) {
            foreach (self::files($file) as $f) {
                if (!unlink($f) && self::fileExists($f)) {
                    $error = error_get_last()['message'] ?? '';
                    throw new RuntimeException('Unable to delete file "{f}": {error}.', ['f' => $f, 'error' => $error]);
                }
            }
        } else {
            $file = Path::resolve($file);

            if (!unlink($file) && self::fileExists($file)) {
                $error = error_get_last()['message'] ?? '';
                throw new RuntimeException('Unable to delete file "{file}": {error}.', ['file' => $file, 'error' => $error]);
            }
        }
    }

    protected static function dirCreateInternal(string|Path $dir, int $mode = 0755): void
    {
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }
    }

    public static function fileGet(string|Path $file): string
    {
        if (($r = @file_get_contents(Path::resolve($file))) === false) {
            throw new FileNotFoundException($file);
        }

        return $r;
    }

    public static function filePut(string|Path $file, string $data): void
    {
        $file = Path::resolve($file);

        self::dirCreateInternal(dirname($file));
        if (file_put_contents($file, $data, LOCK_EX) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException('Unable to write to file "{file}": {error}.', ['file' => $file, 'error' => $error]);
        }
    }

    public static function fileAppend(string|Path $file, string $data): void
    {
        $file = Path::resolve($file);
        self::dirCreateInternal(dirname($file));

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException('Unable to write to file "{file}": {error}.', ['file' => $file, 'error' => $error]);
        }
    }

    public static function fileMove(string|Path $src, string|Path $dst, bool $overwrite = false): void
    {
        $src = Path::resolve($src);
        $dst = Path::resolve($dst);

        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        if (!$overwrite && is_file($dst)) {
            throw new RuntimeException('Unable to move "{src}" to "{dst}": destination file already exists and overwrite is disabled.', ['src' => $src, 'dst' => $dst]);
        }

        if (!is_dir($dir = dirname($dst))) {
            self::dirCreateInternal($dir);
        }

        if (!rename($src, $dst)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException('Unable to move "{src}" to "{dst}": {error}.', ['src' => $src, 'dst' => $dst, 'error' => $error]);
        }
    }

    public static function fileCopy(string|Path $src, string|Path $dst, bool $overwrite = false): void
    {
        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        $src = Path::resolve($src);
        $dst = Path::resolve($dst);

        if ($overwrite || !is_file($dst)) {
            self::dirCreateInternal(dirname($dst));

            if (!copy($src, $dst)) {
                $error = error_get_last()['message'] ?? '';
                throw new RuntimeException('Unable to move "{src}" to "{dst}": {error}.', ['src' => $src, 'dst' => $dst, 'error' => $error]);
            }
        }
    }

    public static function dirExists(string|Path $dir): bool
    {
        return is_dir(Path::resolve($dir));
    }

    protected static function dirDeleteInternal(string|Path $dir): void
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_file($path)) {
                if (!unlink($path)) {
                    $error = error_get_last()['message'] ?? '';
                    throw new RuntimeException('Failed to delete file "{path}": {error}.', ['path' => $path, 'error' => $error]);
                }
            } elseif (is_dir($path)) {
                self::dirDeleteInternal($path);
            } else {
                break;
            }
        }

        if (!rmdir($dir)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException('Failed to delete directory "{dir}": {error}.', ['dir' => $dir, 'error' => $error]);
        }
    }

    public static function dirDelete(string|Path $dir): void
    {
        $dir = Path::resolve($dir);

        if (!is_dir($dir)) {
            return;
        }

        self::dirDeleteInternal($dir);
    }

    public static function dirCreate(string|Path $dir, int $mode = 0755): void
    {
        self::dirCreateInternal(Path::resolve($dir), $mode);
    }

    public static function dirReCreate(string|Path $dir, int $mode = 0755): void
    {
        self::dirDelete($dir);

        self::dirCreate($dir, $mode);
    }

    public static function dirMove(string|Path $src, string|Path $dst, bool $overwrite = false): void
    {
        $src = Path::resolve($src);
        $dst = Path::resolve($dst);

        if (!$overwrite && is_dir($dst)) {
            throw new RuntimeException('Unable to move directory "{src}" to "{dst}": destination directory already exists and overwrite is disabled.', ['src' => $src, 'dst' => $dst]);
        }

        if (!is_dir($dir = dirname($dst))) {
            self::dirCreateInternal($dir);
        }

        if (!rename($src, $dst)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException('Failed to move directory "{src}" to "{dst}": {error}.', ['src' => $src, 'dst' => $dst, 'error' => $error]);
        }
    }

    protected static function dirCopyInternal(string|Path $src, string|Path $dst, bool $overwrite): void
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
                    throw new RuntimeException(
                        'Copy "{srcPath}" file to "{dstPath}" file failed: {error}', ['srcPath' => $srcPath, 'dstPath' => $dstPath, 'error' => $error]
                    );
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

    public static function dirCopy(string|Path $src, string|Path $dst, bool $overwrite = false): void
    {
        $src = Path::resolve($src);
        $dst = Path::resolve($dst);

        if (!is_dir($src)) {
            throw new RuntimeException('Unable to copy directory "{src}" to "{dst}": source directory does not exist.', ['src' => $src, 'dst' => $dst]);
        }
        self::dirCreateInternal($dst);
        self::dirCopyInternal($src, $dst, $overwrite);
    }

    public static function glob(string|Path $pattern, int $flags = 0): array
    {
        $pattern = Path::resolve($pattern);

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

    public static function scandir(string|Path $dir, int $sorting_order = SCANDIR_SORT_ASCENDING): array
    {
        $r = @scandir(Path::resolve($dir), $sorting_order);
        if ($r === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException('Failed to scan directory "{dir}": {error}.', ['dir' => $dir, 'error' => $error]);
        }

        $items = [];
        foreach ($r as $item) {
            if ($item !== '.' && $item !== '..') {
                $items[] = $item;
            }
        }

        return $items;
    }

    public static function files(string|Path $dir): array
    {
        $dir = Path::resolve($dir);

        $files = [];
        foreach (self::glob($dir . (str_contains($dir, '*') ? '' : '/*'), SCANDIR_SORT_ASCENDING) as $item) {
            if (is_file($item)) {
                $files[] = $item;
            }
        }

        return $files;
    }

    public static function directories(string|Path $dir): array
    {
        return self::glob($dir . (str_contains($dir, '*') ? '' : '/*'), GLOB_ONLYDIR);
    }

    public static function getModifiedTime(string|Path $path): ?int
    {
        $v = filemtime(Path::resolve($path));

        return $v === false ? null : $v;
    }

    public static function chmod(string|Path $file, int $mode): void
    {
        if (!chmod(Path::resolve($file), $mode)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException('Failed to change file "{file}" mode to "{mode}": {error}.', ['file' => $file, 'mode' => $mode, 'error' => $error]);
        }
    }
}
