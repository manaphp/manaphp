<?php
declare(strict_types=1);

namespace ManaPHP\Html\Renderer;

use ManaPHP\Component;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 * @property-read \ManaPHP\AliasInterface  $alias
 */
class AssetBundle extends Component implements AssetBundleInterface
{
    protected int $length = 12;

    public function __construct(array $options = [])
    {
        if (isset($options['length'])) {
            $this->length = (int)$options['length'];
        }
    }

    protected function replaceCssUrl(string $file, string $content): string
    {
        $path = dirname(substr($this->alias->resolve($file), strlen($this->alias->get('@public'))));

        return preg_replace_callback(
            '#url\((.+?)\)#', function ($match) use ($path) {
            $url = trim($match[1], '\'"');
            if ($url === '' || str_contains($url, '//')) {
                return $match[0];
            }

            if ($url[0] !== '/') {
                while (str_starts_with($url, '../')) {
                    $path = dirname($path);
                    $url = substr($url, 3);
                }

                $url = rtrim($path, '/\\') . '/' . $url;
            }
            return sprintf('url("%s")', $this->alias->get('@asset') . $url);
        }, $content
        );
    }

    public function bundle(array $files, string $name = 'app'): string
    {
        if (!$files) {
            return '';
        }

        $hash = substr(md5(implode('', $files)), 0, $this->length);
        $extension = pathinfo($files[0], PATHINFO_EXTENSION);
        if ($pos = strpos($extension, '?')) {
            $extension = substr($extension, 0, $pos);
        }

        $bundle = ($name[0] !== '/' ? "/assets/bundle/$name" : $name) . ".$hash.$extension";

        if ($this->config->get('debug') || !is_file($target = $this->alias->get('@public') . "/$bundle")) {
            $r = '';
            foreach ($files as $file) {
                if ($file[0] !== '@') {
                    $file = '@public' . $file;
                }
                $source_file = $file;

                if ($pos = strpos($file, '?')) {
                    $file = substr($file, 0, $pos);
                }

                if (($content = file_get_contents($this->alias->resolve($file))) === false) {
                    throw new FileNotFoundException(['bundled `:file` file is not exists', 'file' => $file]);
                }

                if ($extension === 'css') {
                    $content = $this->replaceCssUrl($file, $content);
                }

                $content = preg_replace('@/\*# sourceMappingURL=[^*]+\s+\*/@', '', $content);

                $r .= PHP_EOL . PHP_EOL . "/* SOURCE_FILE `$source_file` */" . PHP_EOL . $content;
            }

            LocalFS::filePut("@public$bundle", $r);
        }

        return $this->alias->resolve("@asset$bundle");
    }
}