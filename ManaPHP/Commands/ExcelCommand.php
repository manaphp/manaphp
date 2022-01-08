<?php
declare(strict_types=1);

namespace ManaPHP\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Helper\LocalFS;

class ExcelCommand extends Command
{
    /**
     * generate ods from content.xml file
     *
     * @param string $file ods content file path
     *
     * @return void
     */
    public function odsAction(string $file): void
    {
        if (LocalFS::dirExists($file)) {
            $file .= '/content.xml';
        }

        $content = LocalFS::fileGet($file);

        $content = preg_replace('#table:formula="[^\"]+" #', '', $content);
        $content = preg_replace('#table:number-rows-repeated="\d+"#', 'table:number-rows-repeated="100"', $content);
        $content = preg_replace('#office:value-type="float" office:value=".*?" #', '', $content);

        LocalFS::filePut($file . '.xml', $content);
    }
}