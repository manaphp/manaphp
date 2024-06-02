<?php
declare(strict_types=1);

namespace ManaPHP\Http\Router;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ManaPHP\Http\Router\Attribute\MappingInterface;
use ManaPHP\Http\Router\Attribute\RequestMapping;
use ManaPHP\Http\RouterInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use function basename;
use function is_array;
use function str_contains;
use function str_ends_with;
use function str_starts_with;

class MappingScanner implements MappingScannerInterface
{
    #[Autowired] protected RouterInterface $router;

    #[Autowired] protected array $files
        = ['@app/Controllers/*Controller.php',
           '@app/Areas/*/Controllers/*Controller.php'
        ];

    protected function load(): void
    {
        foreach ($this->files as $file) {
            if (str_contains($file, '*')) {
                foreach (LocalFS::glob($file) as $path) {
                    require_once $path;
                }
            } else {
                require_once $file;
            }
        }
    }

    protected function scanController(ReflectionClass $rClass, RequestMapping $requestMapping): void
    {
        $controller = $rClass->getName();

        $prefix = $requestMapping->path ?? '';

        foreach ($rClass->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
            $method = $rMethod->getName();

            $attributes = $rMethod->getAttributes(MappingInterface::class, ReflectionAttribute::IS_INSTANCEOF);
            if ($attributes === []) {
                continue;
            }

            foreach ($attributes as $attribute) {
                /** @var MappingInterface $mapping */
                $mapping = $attribute->newInstance();

                foreach (is_array($mapping->path) ? $mapping->path : [$mapping->path] as $item) {
                    if ($item === null) {
                        $pattern = $prefix . '/' . Str::hyphen(basename($method, 'Action'));
                    } elseif ($item === '') {
                        $pattern = $prefix;
                    } elseif (str_starts_with($item, '/')) {
                        $pattern = $item;
                    } else {
                        $pattern = $prefix . '/' . $item;
                    }
                    $this->router->add($mapping->getMethod(), $pattern, $controller . '::' . $method);
                }
            }
        }
    }

    public function scan(): void
    {
        $this->load();

        foreach (get_declared_classes() as $class) {
            if (!str_ends_with($class, 'Controller')) {
                continue;
            }

            $rClass = new ReflectionClass($class);
            $attributes = $rClass->getAttributes(RequestMapping::class);
            if ($attributes === []) {
                continue;
            }

            $this->scanController($rClass, $attributes[0]->newInstance());
        }
    }

    public function bootstrap(): void
    {
        $this->scan();
    }
}