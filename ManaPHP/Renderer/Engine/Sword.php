<?php
namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;

/**
 * Class ManaPHP\Renderer\Engine\Sword
 *
 * @package renderer\engine
 *
 * @property \ManaPHP\Renderer\Engine\Sword\Compiler $swordCompiler
 */
class Sword extends Component implements EngineInterface
{
    /**
     * @param string $source
     *
     * @return string
     */
    public function getCompiledFile($source)
    {
        if (strpos($source, $this->alias->get('@app')) === 0) {
            $compiled = '@data/sword' . str_replace($this->alias->get('@app'), '', $source);
        } elseif (strpos($source, $this->alias->get('@manaphp')) === 0) {
            $compiled = '@data/sword/_manaphp_/' . str_replace($this->alias->get('@manaphp'), '', $source);
        } else {
            $compiled = '@data/sword/_mixed_/' . md5($source);
        }

        $compiled = $this->alias->resolve($compiled);

        if ($this->configure->debug || !file_exists($compiled) || filemtime($source) > filemtime($compiled)) {
            $this->swordCompiler->compileFile($source, $compiled);
        }

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