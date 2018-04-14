<?php
namespace ManaPHP\Filesystem\Adapter;

use ManaPHP\Component;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\FilesystemInterface;

/**
 * Class ManaPHP\Filesystem\Adapter\File
 *
 * @package filesystem\adapter
 */
class File extends Component implements FilesystemInterface
{
    /**
     * Determine if a file exists.
     *
     * @param string $file
     *
     * @return bool
     */
    public function fileExists($file)
    {
        return file_exists($this->alias->resolve($file));
    }

    /**
     * @param string $file
     *
     * @return int|false
     */
    public function fileSize($file)
    {
        return @filesize($this->alias->resolve($file));
    }

    /**
     * @param string $file
     *
     * @return void
     */
    public function fileDelete($file)
    {
        foreach ($this->files($file) as $f) {
            if (!unlink($f) && $this->fileExists($f)) {
                throw new RuntimeException(['delete `:file` failed: :last_error_message', 'file' => $f]);
            }
        }
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     */
    public function _dirCreate($dir, $mode = 0755)
    {
        /** @noinspection NotOptimalIfConditionsInspection */
        if (!is_dir($dir) && !@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException(['create `:dir` directory failed: :last_error_message'/**m0d79ea0fd2e396837*/, 'dir' => $dir]);
        }
    }

    /**
     * @param string $file
     *
     * @return string|false
     */
    public function fileGet($file)
    {
        return @file_get_contents($this->alias->resolve($file));
    }

    /**
     * @param string $file
     * @param string $data
     *
     * @return void
     */
    public function filePut($file, $data)
    {
        $file = $this->alias->resolve($file);

        $this->_dirCreate(dirname($file));
        if (file_put_contents($file, $data, LOCK_EX) === false) {
            throw new RuntimeException(['write `:file` file failed: :last_error_message'/**m02e67e7a286a4d112*/, 'file' => $file]);
        }
    }

    /**
     * @param string $file
     * @param string $data
     *
     * @return void
     */
    public function fileAppend($file, $data)
    {
        $file = $this->alias->resolve($file);
        $this->_dirCreate(dirname($file));

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            throw new RuntimeException(['write `:file` file failed: :last_error_message'/**m02e67e7a286a4d112*/, 'file' => $file]);
        }
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    public function fileMove($src, $dst, $overwrite = false)
    {
        $src = $this->alias->resolve($src);
        $dst = $this->alias->resolve($dst);

        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        if (!$overwrite && is_file($dst)) {
            throw new RuntimeException(['move `:src` to `:dst` failed: file exists already', 'src' => $src, 'dst' => $dst]);
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
    public function fileCopy($src, $dst, $overwrite = false)
    {
        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        $src = $this->alias->resolve($src);
        $dst = $this->alias->resolve($dst);

        if ($overwrite || !is_file($dst)) {
            $this->_dirCreate(dirname($dst));

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
    public function dirExists($dir)
    {
        return is_dir($this->alias->resolve($dir));
    }

    /**
     * @param string $dir
     */
    protected function _dirDelete($dir)
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
                $this->_dirDelete($path);
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
    public function dirDelete($dir)
    {
        $dir = $this->alias->resolve($dir);

        if (!is_dir($dir)) {
            return;
        }

        $this->_dirDelete($dir);
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     */
    public function dirCreate($dir, $mode = 0755)
    {
        $this->_dirCreate($this->alias->resolve($dir), $mode);
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     */
    public function dirReCreate($dir, $mode = 0755)
    {
        $this->dirDelete($dir);

        $this->dirCreate($dir, $mode);
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    public function dirMove($src, $dst, $overwrite = false)
    {
        $src = $this->alias->resolve($src);
        $dst = $this->alias->resolve($dst);

        if (!$overwrite && is_dir($dst)) {
            throw new RuntimeException(['move `:src` to `:dst` failed: destination directory is exists already', 'src' => $src, 'dst' => $dst]);
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
    protected function _dirCopy($src, $dst, $overwrite)
    {
        foreach (scandir($src, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            if (is_file($srcPath)) {
                if ($overwrite || !file_exists($dstPath)) {
                    if (!copy($srcPath, $dstPath)) {
                        throw new RuntimeException(['copy `:src` file to `:dst` file failed: :last_error_message', 'src' => $srcPath, 'dst' => $dstPath]);
                    }
                }
            } elseif (is_dir($srcPath)) {
                if ($overwrite || !is_dir($dstPath)) {
                    $this->_dirCreate($dstPath);
                    $this->_dirCopy($srcPath, $dstPath, $overwrite);
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
    public function dirCopy($src, $dst, $overwrite = false)
    {
        $src = $this->alias->resolve($src);
        $dst = $this->alias->resolve($dst);

        if (!is_dir($src)) {
            throw new RuntimeException(['copy `:src` directory to `:dst` directory failed: source directory is not exists', 'src' => $src, 'dst' => $dst]);
        }
        $this->_dirCreate($dst);
        $this->_dirCopy($src, $dst, $overwrite);
    }

    /**
     * @param string $pattern
     * @param int    $flags
     *
     * @return array
     */
    public function glob($pattern, $flags = 0)
    {
        $pattern = $this->alias->resolve($pattern);

        if (strpos($pattern, 'phar://') === 0) {
            $dir = dirname($pattern);

            if (!$this->dirExists($dir)) {
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
    public function scandir($dir, $sorting_order = SCANDIR_SORT_ASCENDING)
    {
        $r = @scandir($this->alias->resolve($dir), $sorting_order);
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
    public function files($dir)
    {
        $dir = $this->alias->resolve($dir);

        $files = [];
        foreach ($this->glob($dir . (strpos($dir, '*') === false ? '/*' : ''), SCANDIR_SORT_ASCENDING) as $item) {
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
    public function directories($dir)
    {
        return $this->glob($dir . (strpos($dir, '*') === false ? '/*' : ''), GLOB_ONLYDIR);
    }

    /**
     * @param string $path
     *
     * @return int|false
     */
    public function getModifiedTime($path)
    {
        return filemtime($this->alias->resolve($path));
    }

    /**
     * @param string $file
     * @param int    $mode
     *
     * @return void
     */
    public function chmod($file, $mode)
    {
        if (!chmod($this->alias->resolve($file), $mode)) {
            throw new RuntimeException(['chmod `:file` file to `:mode` mode failed: :last_error_message', 'file' => $file, 'mode' => $mode]);
        }
    }
}