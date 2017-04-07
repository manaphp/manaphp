<?php

namespace ManaPHP\Cli\Controllers;

use ManaPHP\Cli\Controller;

class LiteController extends Controller
{
    /**
     * @CliCommand build manaphp framework lite php file
     * @CliParam   --config,-c  config file name default:@root/manaphp_lite.json
     * @CliParam   --output,-o  output file name default:@root/manaphp_lite.php
     *
     */
    public function defaultCommand()
    {
        if (!$this->filesystem->fileExists('@root/manaphp_lite.json')) {
            $this->filesystem->fileCopy('@manaphp/manaphp_lite.json', '@root/manaphp_lite.json');
        }

        $jsonFile = $this->arguments->get('input:i', '@root/manaphp_lite.json');
        $config = json_decode($this->filesystem->fileGet($jsonFile), true);

        if (isset($config['output'])) {
            $outputFile = $config['output'];
        } else {
            $outputFile = $this->arguments->get('output:o', '@root/manaphp_lite.php');
        }

        $contents = '';
        $classes = $config['classes'];

        foreach ($classes as $c) {
            if (strpos($c, 'ManaPHP\\') !== 0) {
                continue;
            }

            $file = '@manaphp/' . str_replace('\\', '/', substr($c, strpos($c, '\\'))) . '.php';

            if (!$this->filesystem->fileExists($file)) {
                return $this->console->error('`:file` is not missing for `:class` class', ['file' => $file, 'class' => $c]);
            }

            $content = $this->filesystem->fileGet($file);

            $content = preg_replace('#^<\?php#', '', $content, 1) . PHP_EOL;

            if (preg_match('#\s+implements\s+.*#', $content, $matches) === 1) {
                $implements = $matches[0];
                $implements = preg_replace('#[a-zA-Z]+Interface,?#', '', $implements);
                if (str_replace([',', ' ', "\r", "\n"], '', $implements) === 'implements') {
                    $implements = '';
                }
                $content = str_replace($matches[0], $implements, $content);
            }
            $content = preg_replace('#\s*/\*\*.*?\*/#ms', '', $content);//remove comments
            $content = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $content);//remove blank lines
            $content = preg_replace('#([\r\n]+)\s+{#', '{', $content);//repositionClose;

            $contents .= '//' . $c . $content;
        }

        $contents = '<?php' . PHP_EOL . '/**' . PHP_EOL . implode('  ' . PHP_EOL, $classes) . PHP_EOL . '*/' . PHP_EOL . PHP_EOL . $contents;

        $this->filesystem->filePut($outputFile, $contents);

        $this->console->writeLn('lite file generated in `:output` successfully ', ['output' => $outputFile]);
    }
}