<?php
namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;

/**
 * Class ManaPHP\Renderer\Engine\Sword
 *
 * @package renderer\engine
 *
 * @property-read \ManaPHP\Renderer\Engine\Sword\Compiler $swordCompiler
 */
class Sword extends Component implements EngineInterface
{
    /**
     * @var array
     */
    protected $_compiled = [];

    /**
     * @param string $source
     *
     * @return string
     */
    public function getCompiledFile($source)
    {
        if (strpos($source, $root = $this->alias->get('@root')) === 0) {
            $compiled = '@data/sword' . substr($source, strlen($root));
        } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($source, $_SERVER['DOCUMENT_ROOT']) === 0) {
            $compiled = '@data/sword/' . substr($source, strlen($_SERVER['DOCUMENT_ROOT']));
        } else {
            $compiled = "@data/sword/$source";
            if (DIRECTORY_SEPARATOR === '\\') {
                $compiled = str_replace(':', '_', $compiled);
            }
        }

        $compiled = $this->alias->resolve($compiled);

        if (isset($this->_compiled[$compiled])) {
            return $compiled;
        }

        if ($this->configure->debug || !file_exists($compiled) || filemtime($source) > filemtime($compiled)) {
            $this->swordCompiler->compileFile($source, $compiled);
        }

        $this->_compiled[$compiled] = 1;

        return $compiled;
    }

    /**
     * @param string $file
     * @param array  $vars
     *
     * @return void
     */
    public function render($file, $vars = [])
    {
        extract($vars, EXTR_SKIP);

        /** @noinspection PhpIncludeInspection */
        require $this->getCompiledFile($file);
    }
}