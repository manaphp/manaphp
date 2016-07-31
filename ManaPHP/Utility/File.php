<?php
namespace ManaPHP\Utility;

use ManaPHP\Di;
use ManaPHP\Utility\File\Exception;

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
            throw new Exception('Create directory "' . $dir . '" failed: ' . error_get_last()['message']);
        }

        if (file_put_contents($file, $data, LOCK_EX) === false) {
            throw new Exception('Write  file"' . $file . '" failed: ' . error_get_last()['message']);
        }

        clearstatcache(true, $file);
    }

    public static function appendContent($file, $data)
    {
        $file = Di::getDefault()->getShared('alias')->resolve($file);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception('Create directory "' . $dir . '" failed: ' . error_get_last()['message']);
        }

        if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
            throw new Exception('Write  file"' . $file . '" failed: ' . error_get_last()['message']);
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