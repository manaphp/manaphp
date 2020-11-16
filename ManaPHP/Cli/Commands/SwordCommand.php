<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;

/**
 * Class SwordCommand
 *
 * @package ManaPHP\Cli\Commands
 *
 * @property-read \ManaPHP\Html\Renderer\Engine\Sword\Compiler $swordCompiler
 */
class SwordCommand extends Command
{
    /**
     * @param bool $replace
     */
    public function compileAction($replace = false)
    {
        LocalFS::dirDelete('@data/sword');
        $this->console->writeLn('delete `@data/sword` directory success');

        $ext = 'sword';

        foreach (LocalFS::glob("@app/Views/*.$ext") as $item) {
            $this->_compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Views/*/*.$ext") as $item) {
            $this->_compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Areas/*/Views/*/*.$ext") as $item) {
            $this->_compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Areas/*/Views/*.$ext") as $item) {
            $this->_compile($item, $replace);
        }
    }

    /**
     * @param string $file
     * @param bool   $replace
     */
    protected function _compile($file, $replace)
    {
        if ($replace) {
            $compiled = str_replace('.sword', '.phtml', $file);
        } else {
            $compiled = str_replace($this->alias->get('@root'), $this->alias->resolve('@data/sword'), $file);
        }

        $this->swordCompiler->compileFile($file, $compiled);

        $this->console->writeLn(['compiled `:file` file generated', 'file' => $compiled]);
    }
}