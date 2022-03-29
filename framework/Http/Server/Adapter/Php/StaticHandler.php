<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter\Php;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\AliasInterface        $alias
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class StaticHandler extends Component implements StaticHandlerInterface
{
    protected array $mime_types;
    protected array $root_files;
    protected string $doc_root;

    public function __construct()
    {
        $this->doc_root = $this->alias->resolve('@public');

        $this->root_files = $this->getRootFiles();
        $this->mime_types = $this->getMimeTypes();
    }

    protected function getRootFiles(): array
    {
        $files = [];
        foreach (glob($this->doc_root . '/*') as $file) {
            $file = basename($file);
            if ($file[0] === '.' || pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                continue;
            }

            $files[] = basename($file);
        }

        return $files;
    }

    protected function getMimeTypes(): array
    {
        $mime_types = [];
        foreach (file(__DIR__ . '/../../mime.types', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
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

    protected function getStaticFileInternal(): ?string
    {
        $uri = $this->request->getServer('REQUEST_URI');
        $file = ($pos = strpos($uri, '?')) === false ? substr($uri, 1) : substr($uri, 1, $pos - 1);

        if ($file === 'favicon.ico') {
            return '/favicon.ico';
        } elseif (in_array($file, $this->root_files, true)) {
            return $file;
        } elseif (($pos = strpos($file, '/')) === false) {
            return null;
        } else {
            $level1 = substr($file, 0, $pos);
            return in_array($level1, $this->root_files, true) ? $file : null;
        }
    }

    public function getStaticFile(): ?string
    {
        if (($file = $this->getStaticFileInternal()) !== null) {
            return $this->doc_root . '/' . $file;
        } else {
            return null;
        }
    }

    public function isStaticFile(): bool
    {
        return $this->getStaticFileInternal() !== null;
    }

    public function getMimeType(string $file): string
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        return $this->mime_types[$ext] ?? 'application/octet-stream';
    }
}