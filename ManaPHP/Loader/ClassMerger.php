<?php
namespace ManaPHP\Loader;

class ClassMerger
{
    /**
     * @param array $excludes
     *
     * @return string
     */
    public function merge($excludes = [])
    {
        $list = [];

        $contents = '';
        $files = get_included_files();
        foreach (get_declared_interfaces() as $interface) {
            if (strpos($interface, 'ManaPHP\\') === 0) {
                $list[] = $interface;
                if (in_array($interface, $excludes, true)) {
                    $contents .= 'namespace ' . dirname($interface) . ';' . PHP_EOL . 'interface ' . basename($interface) . '{}' . PHP_EOL;
                }
            }
        }

        foreach (get_declared_classes() as $class) {
            if ($class == get_called_class()) {
                continue;
            }

            if (strpos($class, 'ManaPHP\\') === 0 && !in_array($class, $excludes, true)) {
                $list[] = $class;

                foreach ($files as $file) {
                    if (strpos(str_replace('/', '\\', $file), $class . '.php') !== false) {
                        $content = file_get_contents($file);
                        $content = preg_replace('#^<\?php#', '', $content, 1) . PHP_EOL;
                        if (preg_match('#\s+implements\s+.*#', $content, $matches) === 1) {
                            $implements = $matches[0];
                            $implements = preg_replace('#[a-zA-Z]+Interface,?#', '', $implements);
                            if (str_replace([',', ' ', "\r"], '', $implements) === 'implements') {
                                $implements = '';
                            }
                            $content = str_replace($matches[0], $implements, $content);
                        }
                        $content = preg_replace('#\s*/\*\*.*?\*/#ms', '', $content);//remove comments
                        $content = preg_replace('#([\r\n]+)\s*\\1#', '\\1', $content);//remove blank lines
                        $content = preg_replace('#([\r\n]+)\s+{#', '{', $content);//repositionClose;
                        $contents .= $content;
                    }
                }
            }
        }

        return '<?php' . PHP_EOL . '/**' . PHP_EOL . implode('  ' . PHP_EOL, $list) . PHP_EOL . '*/' . PHP_EOL . $contents;
    }
}