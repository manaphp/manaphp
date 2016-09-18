<?php
namespace ManaPHP\Filesystem\Adapter;

use ManaPHP\Component;
use ManaPHP\FilesystemInterface;
use ManaPHP\Filesystem\Adapter\File\Exception as FileException;

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
            throw new FileException('create `:dir` directory failed: :message'/**m0d79ea0fd2e396837*/, ['dir' => $dir, 'message' => FileException::getLastErrorMessage()]);
        }

        if (file_put_contents($file, $data, LOCK_EX) === false) {
            throw new FileException('write `:file` file failed: :message'/**m02e67e7a286a4d112*/, ['file' => $file, 'message' => FileException::getLastErrorMessage()]);
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
            throw new FileException('create `:dir` directory failed: :message'/**m0d79ea0fd2e396837*/, ['dir' => $dir, 'message' => FileException::getLastErrorMessage()]);
        }

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            throw new FileException('write `:file` file failed: :message'/**m02e67e7a286a4d112*/, ['file' => $file, 'message' => FileException::getLastErrorMessage()]);
        }
    }

    /**
     * @param string $old
     * @param string $new
     * @param bool   $overwrite
     *
     * @return void
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function fileMove($old, $new, $overwrite = false)
    {
        if (rtrim($new, '\\/') !== $new) {
            $new .= basename($old);
        }

        if (!$overwrite && is_file($new)) {
            throw new FileException('move `:old` to `:new` failed: file exists already', ['old' => $old, 'new' => $new]);
        }

        if (!rename($this->alias->resolve($old), $this->alias->resolve($new))) {
            throw new FileException('move `:old` to `:new` failed: :message', ['old' => $old, 'new' => $new, 'message' => FileException::getLastErrorMessage()]);
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
                throw new FileException('create `:dir` failed: :message', ['dir' => $dir, 'message' => FileException::getLastErrorMessage()]);
            }

            if (!copy($src, $dst)) {
                throw new FileException('move `:src` to `:dst` failed: :message', ['src' => $src, 'dst' => $dst, 'message' => FileException::getLastErrorMessage()]);
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
                    throw new FileException('delete `:dir` file failed: :message', ['dir' => $dir, 'message' => FileException::getLastErrorMessage()]);
                }
            } elseif (is_dir($path)) {
                if ($recursive) {
                    $this->_dirDelete($path, $recursive);
                }

                if (!rmdir($path)) {
                    throw new FileException('delete `:dir` directory failed: :message', ['dir' => $dir, 'message' => FileException::getLastErrorMessage()]);
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
            throw new FileException('create `:dir` directory failed: :message', ['dir' => $dir, 'message' => FileException::getLastErrorMessage()]);
        }
    }

    /**
     * @param string $old
     * @param string $new
     * @param bool   $overwrite
     *
     * @return void
     * @throws \ManaPHP\Filesystem\Adapter\File\Exception
     */
    public function dirMove($old, $new, $overwrite = false)
    {
        $old = $this->alias->resolve($old);
        $new = $this->alias->resolve($new);

        if (!$overwrite && is_dir($new)) {
            throw new FileException('move `:old` to `:new` failed: destination directory is exists already', ['old' => $old, 'new' => $new]);
        }

        if (!rename($old, $new)) {
            throw new FileException('move `:old` directory to `:new` directory failed: :message',
                ['old' => $old, 'new' => $new, 'message' => FileException::getLastErrorMessage()]);
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
                        throw new FileException('copy `:src` file to `:dst` file failed: :message',
                            ['src' => $srcPath, 'dst' => $dstPath, 'message' => FileException::getLastErrorMessage()]);
                    }
                }
            } elseif (is_dir($srcPath)) {
                if (!@mkdir($dstPath, 0755) && !is_dir($dstPath)) {
                    throw new FileException('create `:dir` directory failed: :message', ['dir' => $dstPath, 'message' => FileException::getLastErrorMessage()]);
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
            throw new FileException('copy `:src` directory to `:dst` directory failed: :message',
                ['src' => $src, 'dst' => $dst, 'message' => FileException::getLastErrorMessage()]);
        }

        $this->_dirCopy($src, $dst, $overwrite);
    }

    /**
     * @param string $pattern
     * @param int    $flags
     *
     * @return mixed
     */
    public function glob($pattern, $flags = 0)
    {
        $r = glob($this->alias->resolve($pattern), $flags);
        $r = $r !== false ? $r : [];

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach ($r as $k => $v) {
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
}