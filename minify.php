<?php
date_default_timezone_set('PRC');

class SourceCodeMinify
{

}

class Application
{
    /**
     * @var string
     */
    protected $_rootPath;

    public function __construct($rootPath = null)
    {
        $this->_rootPath = str_replace('\\', '/', realpath($rootPath ?: (__DIR__ . '/ManaPHP')));
    }

    /**
     * @return string
     */
    public function getRootPath()
    {
        return $this->_rootPath;
    }

    /**
     * @param string $dir
     * @param array  $files
     */
    protected function _getSourceFiles($dir, &$files)
    {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if (strpos($file, '.') === 0) {
                continue;
            }

            $file = str_replace('\\', '/', $dir) . '/' . $file;
            if (is_dir($file)) {
                $this->_getSourceFiles($file, $files);
            } else {
                if (fnmatch('*.php', $file)) {
                    $files[] = $file;
                }
            }
        }

        closedir($dh);
    }

    /**
     * @return array
     */
    public function getSourceFiles()
    {
        $files = [];
        $this->_getSourceFiles($this->_rootPath, $files);

        return $files;
    }

    public function removeComments($content)
    {
        $content = preg_replace('#\s*/\*\*.*?\*/#ms', '', $content);

        return $content;
    }

    public function removeBlankLine($content)
    {
        return preg_replace('#([\r\n]+)\s*\\1#', '\\1', $content);
    }

    public function repositionClose($content)
    {
        return preg_replace('#([\r\n]+)\s+{#', '{', $content);
    }
}

$app = new Application();

var_dump($app->getSourceFiles());
$rootPath = $app->getRootPath();
$dstRootPath = $rootPath . '_' . date('ymd');
$class_file_lines = 0;
$interface_file_lines = 0;
foreach ($app->getSourceFiles() as $file) {
    $dst = str_replace($rootPath, $dstRootPath, $file);
    $dir = dirname($dst);
    if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new \Exception("create directory '$dir'failed: " . error_get_last()['message']);
    }
    $content = file_get_contents($file);
    $content = $app->removeComments($content);
    $content = $app->removeBlankLine($content);
    $content = $app->repositionClose($content);
    $line = strpos($content, "\r") !== false ? substr_count($content, "\r") : substr_count($content, "\n");
    if (strpos($file, 'Interface.php') !== false) {
        $interface_file_lines += $line;
    } else {
        $class_file_lines += $line;
    }
    echo $content;
    file_put_contents($dst, $content);
}

echo "total     lines: ", $class_file_lines + $interface_file_lines, PHP_EOL;
echo 'class     lines: ', $class_file_lines, PHP_EOL;
echo 'interface lines: ', $interface_file_lines, PHP_EOL;