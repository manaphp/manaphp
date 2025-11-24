<?php

declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Alias\Path;
use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;
use function array_merge;
use function asort;
use function closedir;
use function date;
use function fnmatch;
use function is_dir;
use function opendir;
use function php_strip_whitespace;
use function preg_replace;
use function readdir;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strpos;
use function strtr;
use function substr;
use function substr_count;

class FrameworkCommand extends Command
{
    /**
     * @param string $str
     *
     * @return string
     */
    protected function stripWhitespaces(string $str): string
    {
        $tmp = Path::resolve('@runtime/framework/strip.tmp');
        LocalFS::filePut($tmp, $str);
        return php_strip_whitespace($tmp);
        //        $str = preg_replace('#\s*/\*\*.*?\*/#ms', '', $str);//remove comments
        //        $str = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $str);//remove blank lines
        //        $str = preg_replace('#([\r\n]+)\s+{#', '{', $str);//repositionClose;
    }

    /**
     * @param string $dir
     *
     * @return array
     *
     */
    protected function getSourceFiles(string $dir): array
    {
        $files = [];

        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if (str_starts_with($file, '.')) {
                continue;
            }

            $file = strtr($dir, '\\', '/') . '/' . $file;
            if (is_dir($file)) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $files = array_merge($files, $this->getSourceFiles($file));
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
    protected function minify(string $content): string
    {
        $content = preg_replace('#\s*/\*\*.*?\*/#ms', '', $content);//remove comments
        $content = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $content);//remove blank lines
        return preg_replace('#([\r\n]+)\s+{#', '{', $content);//repositionClose;
    }

    /**
     * minify framework source code
     *
     * @return int
     */
    public function minifyAction(): int
    {
        $ManaPHPSrcDir = Path::resolve('@manaphp');
        $ManaPHPDstDir = $ManaPHPSrcDir . '_' . date('ymd');
        $totalClassLines = 0;
        $totalInterfaceLines = 0;
        $totalLines = 0;
        $fileLines = [];
        $sourceFiles = $this->getSourceFiles($ManaPHPSrcDir);
        foreach ($sourceFiles as $file) {
            $dstFile = str_replace($ManaPHPSrcDir, $ManaPHPDstDir, $file);

            $content = $this->minify(LocalFS::fileGet($file));
            $lineCount = substr_count($content, str_contains($content, "\r") ? "\r" : "\n");

            if (str_ends_with($file, 'Interface.php')) {
                $totalInterfaceLines += $lineCount;
            } else {
                $totalClassLines += $lineCount;
            }
            $totalLines += $lineCount;

            $this->console->writeLn($content);
            LocalFS::filePut($dstFile, $content);
            $fileLines[$file] = $lineCount;
        }

        asort($fileLines);

        $i = 1;
        $this->console->writeLn('------------------------------------------------------');

        foreach ($fileLines as $file => $line) {
            $cut_file = substr($file, strpos($file, 'framework'));
            $this->console->writeLn(sprintf('%3d %3d %.2f%% %s', $i++, $line, $line / $totalLines * 100, $cut_file));
        }

        $this->console->writeLn('------------------------------------------------------');
        $this->console->writeLn('total     lines: ' . $totalLines);
        $this->console->writeLn('class     lines: ' . $totalClassLines);
        $this->console->writeLn('interface lines:  ' . $totalInterfaceLines);

        return 0;
    }
}
