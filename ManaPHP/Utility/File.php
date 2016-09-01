<?php
namespace ManaPHP\Utility;

use ManaPHP\Di;
use ManaPHP\Utility\File\Exception as FileException;

class File
{
    public static function exists($file)
    {
        $file = Di::getDefault()->getShared('alias')->resolve($file);

        return file_exists($file);
    }

    public static function setContent($file, $data)
    {
        $file = Di::getDefault()->getShared('alias')->resolve($file);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new FileException('create `:dir` directory failed: :message'/**m0d79ea0fd2e396837*/, ['dir' => $dir, 'message' => Exception::getLastErrorMessage()]);
        }

        if (file_put_contents($file, $data, LOCK_EX) === false) {
            throw new FileException('write `:file` file failed: :message'/**m02e67e7a286a4d112*/, ['file' => $file, 'message' => Exception::getLastErrorMessage()]);
        }

        clearstatcache(true, $file);
    }

    public static function appendContent($file, $data)
    {
        $file = Di::getDefault()->getShared('alias')->resolve($file);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new FileException('create `:dir` directory failed: :message'/**m08b5208830193d69d*/, ['dir' => $dir, 'message' => Exception::getLastErrorMessage()]);
        }

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            throw new FileException('write `:file` file failed: :message'/**m0b9610bc9015aa9da*/, ['file' => $file, 'message' => Exception::getLastErrorMessage()]);
        }

        clearstatcache(true, $file);
    }

    public static function getContent($file)
    {
        $file = Di::getDefault()->getShared('alias')->resolve($file);

        return file_get_contents($file);
    }

    public static function delete($file)
    {
        $file = Di::getDefault()->getShared('alias')->resolve($file);

        unlink($file);
    }
}