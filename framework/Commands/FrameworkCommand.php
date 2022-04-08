<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class FrameworkCommand extends Command
{
    /**
     * @param string $str
     *
     * @return string
     */
    protected function stripWhitespaces(string $str): string
    {
        $tmp = '@runtime/framework/strip.tmp';
        LocalFS::filePut($tmp, $str);
        return php_strip_whitespace($this->alias->resolve($tmp));
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
            if ($file[0] === '.') {
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
        $ManaPHPSrcDir = $this->alias->get('@manaphp');
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