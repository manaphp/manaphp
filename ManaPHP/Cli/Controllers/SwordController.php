<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

/**
 * Class SwordController
 * @package ManaPHP\Cli\Controllers
 *
 * @property-read \ManaPHP\Renderer\Engine\Sword\Compiler $swordCompiler
 */
class SwordController extends Controller
{
    /**
     * @param string $web
     * @param string $asset
     * @param bool   $replace
     */
    public function compileCommand($web = null, $asset = null, $replace = false)
    {
        if ($web) {
            $this->alias->set('@web', $web);
            if ($asset === null && $this->alias->get('@asset') === '') {
                $this->alias->set('@asset', $web);
            }
        }

        if ($asset) {
            $this->alias->set('@asset', $asset);
        }

        $this->filesystem->dirDelete('@data/sword');
        $this->console->writeLn('delete `@data/sword` directory success');

        $ext = 'sword';

        foreach ($this->filesystem->glob("@app/Views/*.$ext") as $item) {
            $this->_compile($item, $replace);
        }

        foreach ($this->filesystem->glob("@app/Views/*/*.$ext") as $item) {
            $this->_compile($item, $replace);
        }

        foreach ($this->filesystem->glob("@app/Areas/*/Views/*/*.$ext") as $item) {
            $this->_compile($item, $replace);
        }

        foreach ($this->filesystem->glob("@app/Areas/*/Views/*.$ext") as $item) {
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