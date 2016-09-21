<?php
namespace ManaPHP;

interface FilesystemInterface
{
    /**
     * @param string $file
     *
     * @return bool
     */
    public function fileExists($file);

    /**
     * @param string $file
     *
     * @return int|false
     */
    public function fileSize($file);

    /**
     * @param string $file
     *
     * @return void
     */
    public function fileDelete($file);

    /**
     * @param string $file
     *
     * @return string|false
     */
    public function fileGet($file);

    /**
     * @param string $file
     * @param string $content
     *
     * @return void
     */
    public function filePut($file, $content);

    /**
     * @param string $file
     * @param string $content
     *
     * @return void
     */
    public function fileAppend($file, $content);

    /**
     * @param string $old
     * @param string $new
     * @param bool   $overwrite
     *
     * @return void
     */
    public function fileMove($old, $new, $overwrite = false);

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    public function fileCopy($src, $dst, $overwrite = false);

    /**
     * @param string $file
     *
     * @return bool
     */
    public function dirExists($file);

    /**
     * @param string $file
     *
     * @return void
     */
    public function dirDelete($file);

    /**
     * @param string $dir
     * @param int    $mode
     *
     * @return void
     *
     * @throws \ManaPHP\Filesystem\Adapter\Exception
     */
    public function dirCreate($dir, $mode = 0755);

    /**
     * @param string $old
     * @param string $new
     * @param bool   $overwrite
     *
     * @return void
     */
    public function dirMove($old, $new, $overwrite = false);

    /**
     * @param string $src
     * @param string $dst
     * @param bool   $overwrite
     *
     * @return void
     */
    public function dirCopy($src, $dst, $overwrite = false);

    /**
     * @param string $pattern
     * @param int    $flags
     *
     * @return array
     */
    public function glob($pattern, $flags = 0);

    /**
     * @param string $dir
     * @param int    $sorting_order
     *
     * @return array
     */
    public function scandir($dir, $sorting_order = SCANDIR_SORT_ASCENDING);
}