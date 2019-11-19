<?php
namespace ManaPHP\Helper;

use ManaPHP\Di;
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
        if (Str::contains($file, '*')) {
            foreach (self::files($file) as $f) {
                if (!unlink($f) && self::fileExists($f)) {
                    throw new RuntimeException(['delete `:file` failed: :last_error_message', 'file' => $f]);
                }
            }
        } else {
            $file = self::$alias->resolve($file);

            if (!unlink($file) && self::fileExists($file)) {
                throw new RuntimeException(['delete `:file` failed: :last_error_message', 'file' => $file]);
            }
        }
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     */
    protected static function _dirCreate($dir, $mode = 0755)
    {
        /** @noinspection NotOptimalIfConditionsInspection */
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException(['create `:dir` directory failed: :last_error_message', 'dir' => $dir]);
        }
    }

    /**
     * @param string $file
     *
     * @return string|false
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

        self::_dirCreate(dirname($file));
        if (file_put_contents($file, $data, LOCK_EX) === false) {
            throw new RuntimeException(['write `:file` file failed: :last_error_message', 'file' => $file]);
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
        self::_dirCreate(dirname($file));

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            throw new RuntimeException(['write `:file` file failed: :last_error_message', 'file' => $file]);
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
            throw new RuntimeException(['move `:src` to `:dst` failed: file exists already', 'src' => $src, 'dst' => $dst]);
        }

        if (!is_dir($dir = dirname($dst))) {
            self::_dirCreate($dir);
        }

        if (!rename($src, $dst)) {
            throw new RuntimeException(['move `:src` to `:dst` failed: :last_error_message', 'src' => $src, 'dst' => $dst]);
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
            self::_dirCreate(dirname($dst));

            if (!copy($src, $dst)) {
                throw new RuntimeException(['move `:src` to `:dst` failed: :last_error_message', 'src' => $src, 'dst' => $dst]);
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
     */
    protected static function _dirDelete($dir)
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_file($path)) {
                if (!unlink($path)) {
                    throw new RuntimeException(['delete `:file` file failed: :last_error_message', 'file' => $path]);
                }
            } elseif (is_dir($path)) {
                self::_dirDelete($path);
            } else {
                break;
            }
        }

        if (!rmdir($dir)) {
            throw new RuntimeException(['delete `:dir` directory failed: :last_error_message', 'dir' => $dir]);
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

        self::_dirDelete($dir);
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     */
    public static function dirCreate($dir, $mode = 0755)
    {
        self::_dirCreate(self::$alias->resolve($dir), $mode);
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
            throw new RuntimeException(['move `:src` to `:dst` failed: destination directory is exists already', 'src' => $src, 'dst' => $dst]);
        }

        if (!is_dir($dir = dirname($dst))) {
            self::_dirCreate($dir);
        }

        if (!rename($src, $dst)) {
            throw new RuntimeException(['move `:src` directory to `:dst` directory failed: :last_error_message', 'src' => $src, 'dst' => $dst]);
        }
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    protected static function _dirCopy($src, $dst, $overwrite)
    {
        foreach (scandir($src, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            if (is_file($srcPath)) {
                if (($overwrite || !file_exists($dstPath)) && !copy($srcPath, $dstPath)) {
                    throw new RuntimeException(['copy `:src` file to `:dst` file failed: :last_error_message', 'src' => $srcPath, 'dst' => $dstPath]);
                }
            } elseif (is_dir($srcPath)) {
                if ($overwrite || !is_dir($dstPath)) {
                    self::_dirCreate($dstPath);
                    self::_dirCopy($srcPath, $dstPath, $overwrite);
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
            throw new RuntimeException(['copy `:src` directory to `:dst` directory failed: source directory is not exists', 'src' => $src, 'dst' => $dst]);
        }
        self::_dirCreate($dst);
        self::_dirCopy($src, $dst, $overwrite);
    }

    /**
     * @param string $pattern
     * @param int    $flags
     *
     * @return array
     */
    public static function glob($pattern, $flags = 0)
    {
        $pattern = self::$alias->resolve($pattern);

        if (strpos($pattern, 'phar://') === 0) {
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
     * @return array
     */
    public static function scandir($dir, $sorting_order = SCANDIR_SORT_ASCENDING)
    {
        $r = @scandir(self::$alias->resolve($dir), $sorting_order);
        if ($r === false) {
            throw new RuntimeException(['scandir `:dir` directory failed: :last_error_message', 'dir' => $dir]);
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
        foreach (self::glob($dir . (strpos($dir, '*') === false ? '/*' : ''), SCANDIR_SORT_ASCENDING) as $item) {
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
        return self::glob($dir . (strpos($dir, '*') === false ? '/*' : ''), GLOB_ONLYDIR);
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
            throw new RuntimeException(['chmod `:file` file to `:mode` mode failed: :last_error_message', 'file' => $file, 'mode' => $mode]);
        }
    }
}

LocalFS::$alias = Di::getDefault()->getShared('alias');