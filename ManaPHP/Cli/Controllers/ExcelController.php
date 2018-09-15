<?php
namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class ExcelController extends Controller
{
    /**
     * @param string $file ods content file path
     */
    public function odsCommand($file)
    {
        if ($this->filesystem->dirExists($file)) {
            $file .= '/content.xml';
        }

        $content = $this->filesystem->fileGet($file);

        $content = preg_replace('#table:formula="[^\"]+" #', '', $content);
        $content = preg_replace('#table:number-rows-repeated="\d+"#', 'table:number-rows-repeated="100"', $content);
        $content = preg_replace('#office:value-type="float" office:value=".*?" #', '', $content);

        $this->filesystem->filePut($file . '.xml', $content);
    }
}