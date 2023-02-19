<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\RouterInterface $router
 */
class StaticHandler extends Component implements StaticHandlerInterface
{
    protected string $root;
    protected array $locations;
    protected string $prefix;
    protected array $mime_types;

    public function __construct(array $options = [])
    {
        $this->root = $options['root'] ?? $_SERVER['DOCUMENT_ROOT'];
        $this->locations = $options['location'] ?? $this->getLocations();
        $this->prefix = $options['prefix'] ?? $this->router->getPrefix();
        $this->mime_types = $this->getMimeTypes();
    }

    protected function getLocations(): array
    {
        $locations = [];
        foreach (glob($this->root . '/*') as $file) {
            $file = basename($file);
            if ($file[0] === '.' || pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                continue;
            }

            $locations[] = '/' . basename($file);
        }

        return $locations;
    }

    protected function getMimeTypes(): array
    {
        $mime_types = [];
        foreach (file(__DIR__ . '/mime.types', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (!str_contains($line, ';')) {
                continue;
            }

            $line = trim($line);
            $line = trim($line, ';');

            $parts = preg_split('#\s+#', $line, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) < 2) {
                continue;
            }

            foreach ($parts as $k => $part) {
                if ($k !== 0) {
                    $mime_types[$part] = $parts[0];
                }
            }
        }

        return $mime_types;
    }

    protected function getFileInternal(string $uri): ?string
    {
        $file = ($pos = strpos($uri, '?')) === false ? $uri : substr($uri, 0, $pos);

        if ($file === $this->prefix || !str_starts_with($file, $this->prefix)) {
            return null;
        }

        $file = substr($file, strlen($this->prefix));

        if (in_array($file, $this->locations, true)) {
            return $file;
        } elseif (($pos = strpos($file, '/', 1)) === false) {
            return null;
        } else {
            $level1 = substr($file, 0, $pos);
            return in_array($level1, $this->locations, true) ? $file : null;
        }
    }

    public function isFile(string $uri): bool
    {
        return $this->getFileInternal($uri) !== null;
    }

    public function getMimeType(string $file): string
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return $this->mime_types[$ext] ?? 'application/octet-stream';
    }

    public function getFile(string $uri): ?string
    {
        $file = $this->root . $this->getFileInternal($uri);

        if ((DIRECTORY_SEPARATOR === '/' ? realpath($file) : str_replace('\\', '/', realpath($file))) === $file) {
            return $file;
        } else {
            return null;
        }
    }
}