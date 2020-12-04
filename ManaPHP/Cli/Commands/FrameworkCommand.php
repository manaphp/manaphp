<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;

class FrameworkCommand extends Command
{
    /**
     * @var string
     */
    protected $_tmp_lite_file = '@tmp/manaphp_lite.tmp';

    /**
     * @param string $str
     *
     * @return string
     */
    protected function _strip_whitespaces($str)
    {
        LocalFS::filePut($this->_tmp_lite_file, $str);
        $str = php_strip_whitespace($this->alias->resolve($this->_tmp_lite_file));
//        $str = preg_replace('#\s*/\*\*.*?\*/#ms', '', $str);//remove comments
//        $str = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $str);//remove blank lines
//        $str = preg_replace('#([\r\n]+)\s+{#', '{', $str);//repositionClose;

        return $str;
    }

    public function __destruct()
    {
        LocalFS::fileDelete($this->_tmp_lite_file);
    }

    /**
     * @param string $dir
     *
     * @return array
     *
     */
    protected function _getSourceFiles($dir)
    {
        $files = [];

        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file[0] === '.') {
                continue;
            }

            $file = strtr($dir, '\\', '/') . '/' . $file;
            if (is_dir($file)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $files = array_merge($files, $this->_getSourceFiles($file));
            } elseif (fnmatch('*.php', $file)) {
                $files[] = $file;
            }
        }

        closedir($dh);

        return $files;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    protected function _minify($content)
    {
        $content = preg_replace('#\s*/\*\*.*?\*/#ms', '', $content);//remove comments
        $content = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $content);//remove blank lines
        $content = preg_replace('#([\r\n]+)\s+{#', '{', $content);//repositionClose;

        return $content;
    }

    /**
     * minify framework source code
     *
     * @return int
     */
    public function minifyAction()
    {
        $ManaPHPSrcDir = $this->alias->get('@manaphp');
        $ManaPHPDstDir = $ManaPHPSrcDir . '_' . date('ymd');
        $totalClassLines = 0;
        $totalInterfaceLines = 0;
        $totalLines = 0;
        $fileLines = [];
        $sourceFiles = $this->_getSourceFiles($ManaPHPSrcDir);
        foreach ($sourceFiles as $file) {
            $dstFile = str_replace($ManaPHPSrcDir, $ManaPHPDstDir, $file);

            $content = $this->_minify(LocalFS::fileGet($file));
            $lineCount = substr_count($content, str_contains($content, "\r") ? "\r" : "\n");

            if (str_ends_with($file, 'Interface.php')) {
                $totalInterfaceLines += $lineCount;
                $totalLines += $lineCount;
            } else {
                $totalClassLines += $lineCount;
                $totalLines += $lineCount;
            }

            $this->console->writeLn($content);
            LocalFS::filePut($dstFile, $content);
            $fileLines[$file] = $lineCount;
        }

        asort($fileLines);

        $i = 1;
        $this->console->writeLn('------------------------------------------------------');

        foreach ($fileLines as $file => $line) {
            $cut_file = substr($file, strpos($file, 'ManaPHP'));
            $this->console->writeLn(['%3d %3d %.3f %s', $i++, $line, $line / $totalLines * 100, $cut_file]);
        }

        $this->console->writeLn('------------------------------------------------------');
        $this->console->writeLn('total     lines: ' . $totalLines);
        $this->console->writeLn('class     lines: ' . $totalClassLines);
        $this->console->writeLn('interface lines:  ' . $totalInterfaceLines);

        return 0;
    }
}