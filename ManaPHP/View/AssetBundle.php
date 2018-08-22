<?php
namespace ManaPHP\View;

use ManaPHP\Component;
use ManaPHP\Exception\FileNotFoundException;

class AssetBundle extends Component implements AssetBundleInterface
{
    /**
     * @var int
     */
    protected $_length = 12;

    /**
     * AssetBundle constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['length'])) {
            $this->_length = $options['length'];
        }
    }

    /**
     * @param array  $files
     * @param string $name
     *
     * @return string
     */
    public function bundle($files, $name = 'app')
    {
        $hash = substr(md5(implode('', $files)), 0, $this->_length);
        $extension = pathinfo($files[0], PATHINFO_EXTENSION);

        $bundle = "assets/bundle/$name.$hash.$extension";

        if (!is_file($target = $this->alias->resolve("@public/$bundle"))) {
            $r = '';
            foreach ($files as $file) {
                if ($file[0] !== '@') {
                    $file = '@public/' . $file;
                }

                if (($content = file_get_contents($this->alias->resolve($file))) === false) {
                    throw new FileNotFoundException(['bundled `:file` file is not exists', 'file' => $file]);
                }

                $r .= "/* SOURCE_FILE `$file` */" . PHP_EOL . $content;
            }

            $this->filesystem->filePut("@public/$bundle", $r);
        }

        return $this->alias->resolve("@$bundle");
    }
}