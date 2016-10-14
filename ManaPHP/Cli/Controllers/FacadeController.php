<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class FacadeController extends Controller
{

    public function frameworkCommand()
    {
        $content = <<<EOD
<?php
namespace ManaPHP\Facade;

use ManaPHP\Facade;

EOD;
        foreach ($this->filesystem->glob('@manaphp/Facade/*.php') as $file) {
            $facadeName = pathinfo($file, PATHINFO_FILENAME);
            if (preg_match('#static\s+(.*)\s+getFacadeInstance*#', $this->filesystem->fileGet($file), $match) !== 1) {
                continue;
            }

            $r = $this->generate($facadeName, $match[1]);
            $content .= PHP_EOL . PHP_EOL . $r;
        }

        $this->filesystem->filePut('@manaphp/.ide.helper.facade.php', $content);
    }

    /**
     * @param string $facadeClassName
     * @param string $interfaceName
     *
     * @return mixed
     */
    public function generate($facadeClassName, $interfaceName)
    {
        $content = <<<EOD
/** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */

/**
  * @method  static $interfaceName getFacadeInstance()
  */
class $facadeClassName extends Facade
{

EOD;
        $rc = new \ReflectionClass($interfaceName);
        $lines = file($rc->getFileName());
        foreach ($rc->getMethods() as $method) {
            $comment = '    ' . $method->getDocComment();
            $content .= $comment . PHP_EOL;

            $signature = '';
            for ($i = $method->getStartLine(); $i <= $method->getEndLine(); $i++) {
                $signature .= ' ' . $lines[$i - 1];
            }
            $signature = rtrim(rtrim($signature), ';') . '{}' . PHP_EOL . PHP_EOL;
            $content .= preg_replace('#public\s+function#', 'public static function', $signature);
        }
        $content = rtrim($content) . PHP_EOL . '}';

        return $content;
    }
}