<?php
namespace ManaPHP\Utility {

    use ManaPHP\Utility\File\Exception;

    class File
    {
        public static function exists($file)
        {
            return file_exists($file);
        }

        public static function setContent($file, $data)
        {
            $dir = dirname($file);
            if (!@mkdir($dir, 0755, true) && !is_dir($$dir)) {
                throw new Exception('Create directory "' . $dir . '" failed: ' . error_get_last()['message']);
            }

            if (file_put_contents($file, $data, LOCK_EX) === false) {
                throw new Exception('Write  file"' . $file . '" failed: ' . error_get_last()['message']);
            }

            clearstatcache(true, $file);
        }

        public static function appendContent($file, $data)
        {
            $dir = dirname($file);
            if (!@mkdir($dir, 0755, true) && !is_dir($$dir)) {
                throw new Exception('Create directory "' . $dir . '" failed: ' . error_get_last()['message']);
            }

            if (file_put_contents($file, $data, LOCK_EX | FILE_APPEND) === false) {
                throw new Exception('Write  file"' . $file . '" failed: ' . error_get_last()['message']);
            }

            clearstatcache(true, $file);
        }

        public static function getContent($file)
        {
            return file_get_contents($file);
        }

        public static function delete($file)
        {
            unlink($file);
        }
    }
}