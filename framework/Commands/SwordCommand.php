<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\AliasInterface                      $alias
 * @property-read \ManaPHP\Html\Renderer\Engine\Sword\Compiler $swordCompiler
 */
class SwordCommand extends Command
{
    /**
     * precompile sword template
     *
     * @param bool $replace
     *
     * @return void
     */
    public function compileAction(bool $replace = false): void
    {
        LocalFS::dirDelete('@data/sword');
        $this->console->writeLn('delete `@data/sword` directory success');

        $ext = 'sword';

        foreach (LocalFS::glob("@app/Views/*.$ext") as $item) {
            $this->compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Views/*/*.$ext") as $item) {
            $this->compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Areas/*/Views/*/*.$ext") as $item) {
            $this->compile($item, $replace);
        }

        foreach (LocalFS::glob("@app/Areas/*/Views/*.$ext") as $item) {
            $this->compile($item, $replace);
        }
    }

    /**
     * @param string $file
     * @param bool   $replace
     *
     * @return void
     */
    protected function compile(string $file, bool $replace): void
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