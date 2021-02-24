<?php

namespace ManaPHP\Html\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Html\Renderer\EngineInterface;

/**
 * @property-read \ManaPHP\AliasInterface                      $alias
 * @property-read \ManaPHP\Html\Renderer\Engine\Sword\Compiler $swordCompiler
 */
class Sword extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $doc_root;

    /**
     * @var array
     */
    protected $compiled = [];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->doc_root = $options['doc_root'] ?? $_SERVER['DOCUMENT_ROOT'];
    }

    /**
     * @param string $source
     *
     * @return string
     */
    public function getCompiledFile($source)
    {
        if (str_starts_with($source, $root = $this->alias->get('@root'))) {
            $compiled = '@data/sword' . substr($source, strlen($root));
        } elseif ($this->doc_root !== '' && str_starts_with($source, $this->doc_root)) {
            $compiled = '@data/sword/' . substr($source, strlen($this->doc_root));
        } else {
            $compiled = "@data/sword/$source";
            if (DIRECTORY_SEPARATOR === '\\') {
                $compiled = str_replace(':', '_', $compiled);
            }
        }

        $compiled = $this->alias->resolve($compiled);

        if (APP_DEBUG || !file_exists($compiled) || filemtime($source) > filemtime($compiled)) {
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

        if (!isset($this->compiled[$file])) {
            $this->compiled[$file] = $this->getCompiledFile($file);
        }

        /** @noinspection PhpIncludeInspection */
        require $this->compiled[$file];
    }
}