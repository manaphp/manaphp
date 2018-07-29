<?php
namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Renderer\EngineInterface;

/**
 * Class ManaPHP\Renderer\Engine\Sword
 *
 * @package renderer\engine
 */
class Sword extends Component implements EngineInterface
{
    /**
     * @param string $file
     * @param array  $vars
     *
     * @return void
     */
    public function render($file, $vars = [])
    {
        if (strpos($file, $this->alias->get('@app')) === 0) {
            $_compiledFile = $this->alias->resolve('@data/sword' . str_replace($this->alias->get('@app'), '', $file));
        } elseif (strpos($file, $this->alias->get('@manaphp')) === 0) {
            $_compiledFile = $this->alias->resolve('@data/sword/manaphp/' . str_replace($this->alias->get('@manaphp'), '', $file));
        } else {
            $_compiledFile = $this->alias->resolve('@data/sword/mixed/' . md5($file));
        }

        if ($this->configure->debug || !file_exists($_compiledFile) || filemtime($file) > filemtime($_compiledFile)) {
            $this->_di->getShared('swordCompiler')->compileFile($file, $_compiledFile);
        }

        extract($vars, EXTR_SKIP);

        /** @noinspection PhpIncludeInspection */
        require $_compiledFile;
    }
}