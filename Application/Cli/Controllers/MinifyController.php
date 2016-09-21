<?php
namespace Application\Cli\Controllers;

use ManaPHP\Cli\Controller;
use ManaPHP\Utility\Text;

class MinifyController extends Controller
{
    protected function _getSourceFiles($dir)
    {
        $files = [];

        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if (Text::startsWith($file, '.')) {
                continue;
            }

            $file = str_replace('\\', '/', $dir) . '/' . $file;
            if (is_dir($file)) {
                $files = array_merge($files, $this->_getSourceFiles($file));
            } else {
                if (fnmatch('*.php', $file)) {
                    $files[] = $file;
                }
            }
        }

        closedir($dh);

        return $files;
    }

    protected function _minify($content)
    {
        $content = preg_replace('#\s*/\*\*.*?\*/#ms', '', $content);//remove comments
        $content = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $content);//remove blank lines
        $content = preg_replace('#([\r\n]+)\s+{#', '{', $content);//repositionClose;

        return $content;
    }

    /**
     * @description minify the ManaPHP framework source code
     * @return int
     */
    public function defaultCommand()
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

            $content = $this->_minify($this->filesystem->fileGet($file));
            $lineCount = Text::contains($content, "\r") ? substr_count($content, "\r") : substr_count($content, "\n");

            if (Text::contains($file, 'Interface.php')) {
                $totalInterfaceLines += $lineCount;
                $totalLines += $lineCount;
            } else {
                $totalClassLines += $lineCount;
                $totalLines += $lineCount;
            }

            $this->console->writeLn($content);
            $this->filesystem->filePut($dstFile, $content);
            $fileLines[$file] = $lineCount;
        }

        asort($fileLines);

        $i = 1;
        $this->console->writeLn('------------------------------------------------------');

        foreach ($fileLines as $file => $line) {
            $this->console->writeLn(sprintf('%3d %3d %.3f', $i++, $line, $line / $totalLines * 100) . ' ' . substr($file, strpos($file, 'ManaPHP')));
        }

        $this->console->writeLn('------------------------------------------------------');
        $this->console->writeLn('total     lines: ' . $totalLines);
        $this->console->writeLn('class     lines: ' . $totalClassLines);
        $this->console->writeLn('interface lines:  ' . $totalInterfaceLines);

        return 0;
    }
}