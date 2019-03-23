<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class FrameworkController extends Controller
{
    /**
     * @var string
     */
    protected $_tmpLiteFile = '@root/manaphp_lite.tmp';

    /**
     * build manaphp framework lite php file
     *
     * @param string $input input file name
     * @param string $output output file name
     * @param int    $interfaces_keep
     * @param int    $whitespaces_keep
     * @param int    $namespace_keep
     *
     * @return int
     */
    public function liteCommand(
        $input = '@root/manaphp_lite.json',
        $output = '@root/manaphp_lite.php',
        $interfaces_keep = 0,
        $whitespaces_keep = 0,
        $namespace_keep = 0
    ) {
        if (!$this->filesystem->fileExists('@root/manaphp_lite.json')) {
            $this->filesystem->fileCopy('@manaphp/manaphp_lite.json', '@root/manaphp_lite.json');
        }

        $config = json_decode($this->filesystem->fileGet($input), true);

        if (isset($config['output'])) {
            $output = $config['output'];
        }

        $contents = '';

        $prevClassNamespace = '';
        foreach ((array)$config['classes'] as $className) {
            if (strpos($className, 'ManaPHP\\') !== 0) {
                continue;
            }

            $this->console->writeLn($className . '...');

            $classFile = '@manaphp/' . strtr(substr($className, strpos($className, '\\')), '\\', '/') . '.php';

            if (!$this->filesystem->fileExists($classFile)) {
                return $this->console->error(['`:file` is not missing for `:class` class', 'file' => $classFile, 'class' => $className]);
            }

            $classContent = $this->filesystem->fileGet($classFile);
            if ($namespace_keep) {
                if (preg_match('#namespace\s+([^;]+);#', $classContent, $matches) === 1) {
                    $classNamespace = $matches[1];
                    if ($classNamespace === $prevClassNamespace) {
                        $classContent = str_replace($matches[0], '', $classContent);
                    }
                    $prevClassNamespace = $classNamespace;
                } else {
                    $this->console->writeLn(['`:class` class namespace is not found', 'class' => $className]);
                }
            }

            if (!$interfaces_keep && preg_match('#\s+implements\s+.*#', $classContent, $matches) === 1) {
                $implements = $matches[0];
                $implements = preg_replace('#[a-zA-Z]+Interface,?#', '', $implements);
                if (str_replace([',', ' ', "\r", "\n"], '', $implements) === 'implements') {
                    $implements = '';
                }
                $classContent = str_replace($matches[0], $implements, $classContent);
            }

            if (!$whitespaces_keep) {
                $classContent = $this->_strip_whitespaces($classContent);
            }

            $contents .= '/**' . $className . '*/' . preg_replace('#^\s*<\?php\s*#', '', $classContent, 1) . PHP_EOL;
        }

        $contents = '<?php' . PHP_EOL . $contents;

        $this->filesystem->filePut($output, $contents);

        $this->console->writeLn(['lite file generated in `:output` successfully ', 'output' => $output]);

        return 0;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function _strip_whitespaces($str)
    {
        $this->filesystem->filePut($this->_tmpLiteFile, $str);
        $str = php_strip_whitespace($this->alias->resolve($this->_tmpLiteFile));
//        $str = preg_replace('#\s*/\*\*.*?\*/#ms', '', $str);//remove comments
//        $str = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $str);//remove blank lines
//        $str = preg_replace('#([\r\n]+)\s+{#', '{', $str);//repositionClose;

        return $str;
    }

    public function __destruct()
    {
        $this->filesystem->fileDelete($this->_tmpLiteFile);
    }

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

    protected function _minify($content)
    {
        $content = preg_replace('#\s*/\*\*.*?\*/#ms', '', $content);//remove comments
        $content = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $content);//remove blank lines
        $content = preg_replace('#([\r\n]+)\s+{#', '{', $content);//repositionClose;

        return $content;
    }

    /**
     * minify framework source code
     * @return int
     */
    public function minifyCommand()
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
            $lineCount = substr_count($content, strpos($content, "\r") !== false ? "\r" : "\n");

            if (strpos($file, 'Interface.php')) {
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

    /**
     * generate manaphp_lite.json file
     *
     * @param string $source
     */
    public function genJsonCommand($source)
    {
        $classNames = [];
        /** @noinspection ForeachSourceInspection */
        /** @noinspection PhpIncludeInspection */
        foreach (require $source as $className) {
            if (preg_match('#^ManaPHP\\\\.*$#', $className)) {
                $classNames[] = $className;
            }
        }

        $output = __DIR__ . '/manaphp_lite.json';
        if ($this->filesystem->fileExists($output)) {
            $data = json_encode($this->filesystem->fileGet($output));
        } else {
            $data = [
                'output' => '@root/manaphp.lite'
            ];
        }

        $data['classes'] = $classNames;

        $this->filesystem->filePut($output, json_encode($data, JSON_PRETTY_PRINT));
        $this->console->writeLn(json_encode($classNames, JSON_PRETTY_PRINT));
    }
}