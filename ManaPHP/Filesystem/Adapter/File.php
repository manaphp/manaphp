<?php
namespace ManaPHP\Filesystem\Adapter;

use ManaPHP\Component;
use ManaPHP\Filesystem\Adapter\File\Exception as FileException;
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
        @unlink($this->alias->resolve($file));
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
     *
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function filePut($file, $data)
    {
        $file = $this->alias->resolve($file);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new FileException('create `:dir` directory failed: :last_error_message'/**m0d79ea0fd2e396837*/, ['dir' => $dir]);
        }

        if (file_put_contents($file, $data, LOCK_EX) === false) {
            throw new FileException('write `:file` file failed: :last_error_message'/**m02e67e7a286a4d112*/, ['file' => $file]);
        }
    }

    /**
     * @param string $file
     * @param string $data
     *
     * @return void
     *
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function fileAppend($file, $data)
    {
        $file = $this->alias->resolve($file);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new FileException('create `:dir` directory failed: :last_error_message'/**m0d79ea0fd2e396837*/, ['dir' => $dir]);
        }

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            throw new FileException('write `:file` file failed: :last_error_message'/**m02e67e7a286a4d112*/, ['file' => $file]);
        }
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function fileMove($src, $dst, $overwrite = false)
    {
        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        if (!$overwrite && is_file($dst)) {
            throw new FileException('move `:src` to `:dst` failed: file exists already', ['src' => $src, 'dst' => $dst]);
        }

        if (!rename($this->alias->resolve($src), $this->alias->resolve($dst))) {
            throw new FileException('move `:src` to `:dst` failed: :last_error_message', ['src' => $src, 'dst' => $dst]);
        }
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function fileCopy($src, $dst, $overwrite = false)
    {
        $src = $this->alias->resolve($src);
        $dst = $this->alias->resolve($dst);

        if (rtrim($dst, '\\/') !== $dst) {
            $dst .= basename($src);
        }

        if ($overwrite || !is_file($dst)) {
            $dir = dirname($dst);
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new FileException('create `:dir` failed: :last_error_message', ['dir' => $dir]);
            }

            if (!copy($src, $dst)) {
                throw new FileException('move `:src` to `:dst` failed: :last_error_message', ['src' => $src, 'dst' => $dst]);
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
     * @param bool   $recursive
     *
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    protected function _dirDelete($dir, $recursive)
    {
        foreach (scandir($dir, SCANDIR_SORT_NONE) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_file($path)) {
                if (!unlink($path)) {
                    throw new FileException('delete `:dir` file failed: :last_error_message', ['dir' => $dir]);
                }
            } elseif (is_dir($path)) {
                if ($recursive) {
                    $this->_dirDelete($path, $recursive);
                }

                if (!rmdir($path)) {
                    throw new FileException('delete `:dir` directory failed: :last_error_message', ['dir' => $dir]);
                }
            } else {
                break;
            }
        }
    }

    /**
     * @param string $dir
     * @param bool   $recursive
     *
     * @return void
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function dirDelete($dir, $recursive = false)
    {
        $dir = $this->alias->resolve($dir);

        if (!is_dir($dir)) {
            return;
        }

        $this->_dirDelete($dir, $recursive);
    }

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     *
     * @throws \ManaPHP\Filesystem\Adapter\Exception
     */
    public function dirCreate($dir, $mode = 0755)
    {
        $dir = $this->alias->resolve($dir);

        if (@mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new FileException('create `:dir` directory failed: :last_error_message', ['dir' => $dir]);
        }
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function dirMove($src, $dst, $overwrite = false)
    {
        $src = $this->alias->resolve($src);
        $dst = $this->alias->resolve($dst);

        if (!$overwrite && is_dir($dst)) {
            throw new FileException('move `:src` to `:dst` failed: destination directory is exists already', ['src' => $src, 'dst' => $dst]);
        }

        if (!rename($src, $dst)) {
            throw new FileException('move `:src` directory to `:dst` directory failed: :last_error_message', ['src' => $src, 'dst' => $dst]);
        }
    }

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
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
                        throw new FileException('copy `:src` file to `:dst` file failed: :last_error_message', ['src' => $srcPath, 'dst' => $dstPath]);
                    }
                }
            } elseif (is_dir($srcPath)) {
                if (!@mkdir($dstPath, 0755) && !is_dir($dstPath)) {
                    throw new FileException('create `:dir` directory failed: :last_error_message', ['dir' => $dstPath]);
                }

                if ($overwrite || !is_dir($dstPath)) {
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
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function dirCopy($src, $dst, $overwrite = false)
    {
        $src = $this->alias->resolve($src);
        $dst = $this->alias->resolve($dst);

        if (!is_dir($src)) {
            throw new FileException('copy `:src` directory to `:dst` directory failed: source directory is not exists', ['src' => $src, 'dst' => $dst]);
        }

        if (!@mkdir($dst, 0755, true) && !is_dir($dst)) {
            throw new FileException('copy `:src` directory to `:dst` directory failed: :last_error_message', ['src' => $src, 'dst' => $dst]);
        }

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
        $r = glob($this->alias->resolve($pattern), $flags);
        $r = $r !== false ? $r : [];

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach ($r as $k => $v) {
                /** @noinspection AlterInForeachInspection */
                $r[$k] = str_replace('\\', '/', $v);
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
        $r = scandir($this->alias->resolve($dir), $sorting_order);

        return is_array($r) ? $r : [];
    }

    /**
     * @param string $dir
     * @param string $pattern
     *
     * @return array
     */
    public function files($dir, $pattern = null)
    {
        $dir = $this->alias->resolve($dir);

        $files = [];
        foreach ($this->scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (!is_file($dir . '/' . $item)) {
                continue;
            }

            if ($pattern === null || fnmatch($pattern, $item)) {
                $files[] = $dir . '/' . $item;
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
        return $this->glob($dir . '/*', GLOB_ONLYDIR);
    }
}