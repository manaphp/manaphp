<?php

namespace ManaPHP\Helper;

use ManaPHP\Di\Container;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\RuntimeException;

class LocalFS
{
    /**
     * @var \ManaPHP\AliasInterface
     */
    public static $alias;

    /**
     * Determine if a file exists.
     *
     * @param string $file
     *
     * @return bool
     */
    public static function fileExists($file)
    {
        return is_file(self::$alias->resolve($file));
    }

    /**
     * @param string $file
     *
     * @return int|false
     */
    public static function fileSize($file)
    {
        return @filesize(self::$alias->resolve($file));
    }

    /**
     * @param string $file
     *
     * @return void
     */
    public static function fileDelete($file)
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

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     */
    protected static function dirCreateInternal($dir, $mode = 0755)
    {
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }
    }

    /**
     * @param string $file
     *
     * @return string
     */
    public static function fileGet($file)
    {
        if (($r = @file_get_contents(self::$alias->resolve($file))) === false) {
            throw new FileNotFoundException($file);
        }

        return $r;
    }

    /**
     * @param string $file
     * @param string $data
     *
     * @return void
     */
    public static function filePut($file, $data)
    {
        $file = self::$alias->resolve($file);

        self::dirCreateInternal(dirname($file));
        if (file_put_contents($file, $data, LOCK_EX) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['write `%s` file failed: %s', $file, $error]);
        }
    }

    /**
     * @param string $file
     * @param string $data
     *
     * @return void
     */
    public static function fileAppend($file, $data)
    {
        $file = self::$alias->resolve($file);
        self::dirCreateInternal(dirname($file));

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['write `%s` file failed: %s', $file, $error]);
        }
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    public static function fileMove($src, $dst, $overwrite = false)
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

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    public static function fileCopy($src, $dst, $overwrite = false)
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

    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function dirExists($dir)
    {
        return is_dir(self::$alias->resolve($dir));
    }

    /**
     * @param string $dir
     *
     * @return void
     */
    protected static function dirDeleteInternal($dir)
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

    /**
     * @param string $dir
     *
     * @return void
     */
    public static function dirDelete($dir)
    {
        $dir = self::$alias->resolve($dir);

        if (!is_dir($dir)) {
            return;
        }

        self::dirDeleteInternal($dir);
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     */
    public static function dirCreate($dir, $mode = 0755)
    {
        self::dirCreateInternal(self::$alias->resolve($dir), $mode);
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     */
    public static function dirReCreate($dir, $mode = 0755)
    {
        self::dirDelete($dir);

        self::dirCreate($dir, $mode);
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    public static function dirMove($src, $dst, $overwrite = false)
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

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    protected static function dirCopyInternal($src, $dst, $overwrite)
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

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    public static function dirCopy($src, $dst, $overwrite = false)
    {
        $src = self::$alias->resolve($src);
        $dst = self::$alias->resolve($dst);

        if (!is_dir($src)) {
            throw new RuntimeException(['copy `%s` to `%s` failed: source directory is not exists', $src, $dst]);
        }
        self::dirCreateInternal($dst);
        self::dirCopyInternal($src, $dst, $overwrite);
    }

    /**
     * @param string $pattern
     * @param int    $flags
     *
     * @return string[]
     */
    public static function glob($pattern, $flags = 0)
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

    /**
     * @param string $dir
     * @param int    $sorting_order
     *
     * @return string[]
     */
    public static function scandir($dir, $sorting_order = SCANDIR_SORT_ASCENDING)
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

    /**
     * @param string $dir
     *
     * @return array
     */
    public static function files($dir)
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

    /**
     * @param string $dir
     *
     * @return array
     */
    public static function directories($dir)
    {
        return self::glob($dir . (str_contains($dir, '*') ? '' : '/*'), GLOB_ONLYDIR);
    }

    /**
     * @param string $path
     *
     * @return int|false
     */
    public static function getModifiedTime($path)
    {
        return filemtime(self::$alias->resolve($path));
    }

    /**
     * @param string $file
     * @param int    $mode
     *
     * @return void
     */
    public static function chmod($file, $mode)
    {
        if (!chmod(self::$alias->resolve($file), $mode)) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['chmod `%s` file to `%s` mode failed: %s', $file, $mode, $error]);
        }
    }
}

LocalFS::$alias = Container::getDefault()->getShared('alias');