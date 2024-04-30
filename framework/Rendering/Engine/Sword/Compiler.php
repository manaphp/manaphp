<?php
declare(strict_types=1);

namespace ManaPHP\Rendering\Engine\Sword;

use JetBrains\PhpStorm\ArrayShape;
use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Helper\Str;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\UrlInterface;
use function count;
use function dirname;
use function in_array;
use function is_array;
use function strlen;

class Compiler
{
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected UrlInterface $url;
    #[Autowired] protected RouterInterface $router;

    #[Autowired] protected int $hash_length = 12;
    #[Autowired] protected array $directives = [];
    #[Autowired] protected array $rawTags = ['{!!', '!!}'];
    #[Autowired] protected array $escapedTags = ['{{', '}}'];

    protected bool $foreachelse_used = false;
    protected array $safe_functions
        = [
            'e',
            'url',
            'action',
            'asset',
            'csrf_token',
            'csrf_field',
            'date',
            'html',
            'attr_nv',
            'attr_inv',
            'partial',
            'json',
            'base_url'
        ];

    public function __construct(?string $safe_functions = null)
    {
        if ($safe_functions !== null) {
            $this->safe_functions = array_merge(
                $this->safe_functions,
                preg_split('#[\s,]+#', $safe_functions, PREG_SPLIT_NO_EMPTY)
            );
        }
    }

    protected function addFileHash(string $str): string
    {
        return preg_replace_callback(
            '#="(/[-\w/.]+\.\w+)"#', function ($match) {
            $url = $match[1];

            if (in_array(pathinfo($url, PATHINFO_EXTENSION), ['htm', 'html', 'php'], true)) {
                return $match[0];
            }

            $path = '@public' . $url;
            $file = $this->alias->resolve($path);
            if (!is_file($file)) {
                return $match[0];
            }
            $hash = substr(md5_file($file), 0, $this->hash_length);

            return "=\"$url?v=$hash\"";
        }, $str
        );
    }

    protected function completeRelativeLinks(string $file, string $str): string
    {
        if ($str === '#' || str_contains($str, '://') || str_starts_with($str, '//')) {
            return $str;
        }

        if ($str[0] === '/') {
            return $str;
        }

        $area = preg_match('#/Areas/([^/]+)#i', $file, $match) ? Str::snakelize($match[1]) : null;
        if (($pos = strripos($file, '/views/')) === false || strrpos($file, '_layout')) {
            return $str;
        }

        $parts = explode('/', substr($file, $pos + 7));
        if (count($parts) === 1) {
            $controller = Str::snakelize(pathinfo($parts[0], PATHINFO_FILENAME));
        } else {
            $controller = Str::snakelize($parts[0]);
        }
        if (str_contains($str, '/')) {
            $absolute = $area ? "/$area/$str" : "/$str";
        } else {
            $absolute = $area ? "/$area/$controller/$str" : "/$controller/$str";
        }

        return $absolute;
    }

    protected function completeLinks(string $file, string $str): string
    {
        return preg_replace_callback(
            '#\b((?:ajax|axios\.)\w*\\(["\'`])([^/][\w\-/:.]+)#',
            fn($match) => $match[1] . $this->completeRelativeLinks($file, $match[2]),
            $str
        );
    }

    public function compileString(string $value): string
    {
        $result = '';

        // Here we will loop through all the tokens returned by the Zend lexer and
        // parse each one into the corresponding valid PHP. We will then have this
        // template as the correctly rendered PHP that can be rendered natively.
        foreach (token_get_all($value) as $token) {
            if (is_array($token)) {
                list($id, $content) = $token;
                if ($id === T_INLINE_HTML) {
                    $content = $this->compileStatements($content);
                    $content = $this->compileComments($content);
                    $content = $this->compileEchos($content);
                }
            } else {
                $content = $token;
            }

            $result .= $content;
        }

        if ($this->hash_length) {
            $result = $this->addFileHash($result);
        }

        return $result;
    }

    public function compileFile(string $source, string $compiled): static
    {
        $source = $this->alias->resolve($source);
        $compiled = $this->alias->resolve($compiled);

        $dir = dirname($compiled);

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }

        if (($str = @file_get_contents($source)) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new InvalidArgumentException(['read `{1}` sword source file failed: {2}', $source, $error]);
        }

        $result = $this->compileString($str);

        $result = $this->completeLinks($source, $result);

        if (file_put_contents($compiled, $result, LOCK_EX) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new RuntimeException(['write `{1}` compiled file file failed: {2}', $compiled, $error]);
        }

        return $this;
    }

    protected function compileComments(string $value): string
    {
        $pattern = sprintf('/%s--(.*?)--%s/s', $this->escapedTags[0], $this->escapedTags[1]);

        return preg_replace($pattern, '<?php /*$1*/ ?> ', $value);
    }

    protected function compileEchos(string $value): string
    {
        foreach ($this->getEchoMethods() as $method => $length) {
            $value = $this->$method($value);
        }

        return $value;
    }

    #[ArrayShape(['compileRawEchos' => 'int', 'compileEscapedEchos' => 'int'])]
    protected function getEchoMethods(): array
    {
        $methods = [
            'compileRawEchos'     => strlen(stripcslashes($this->rawTags[0])),
            'compileEscapedEchos' => strlen(stripcslashes($this->escapedTags[0])),
        ];

        uksort(
            $methods, static function ($method1, $method2) use ($methods) {
            // Ensure the longest tags are processed first
            if ($methods[$method1] > $methods[$method2]) {
                return -1;
            }
            if ($methods[$method1] < $methods[$method2]) {
                return 1;
            }

            // give preference to raw tags (assuming they've overridden)
            if ($method1 === 'compileRawEchos') {
                return -1;
            }
            if ($method2 === 'compileRawEchos') {
                return 1;
            }

            if ($method1 === 'compileEscapedEchos') {
                return -1;
            }
            if ($method2 === 'compileEscapedEchos') {
                return 1;
            }

            return 0;
        }
        );

        return $methods;
    }

    /**
     * Compile Sword statements that start with "@".
     *
     * @param string $value
     *
     * @return string
     */
    protected function compileStatements(string $value): string
    {
        $callback = function ($match) {
            if (method_exists($this, $method = 'compile_' . $match[1])) {
                $match[0] = $this->$method($match[3] ?? null);
            } elseif (isset($this->directives[$match[1]])) {
                $func = $this->directives[$match[1]];
                $match[0] = $func($match[3] ?? null);
            }

            return isset($match[3]) ? $match[0] : $match[0] . $match[2];
        };

        return preg_replace_callback(
        /** @lang text */ '/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $callback,
            $value
        );
    }

    protected function compileRawEchos(string $value): string
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->rawTags[0], $this->rawTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3];

            return $matches[1]
                ? substr($matches[0], 1)
                : '<?= ' . $this->compileEchoDefaults($matches[2]) . '; ?>' . $whitespace;
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    protected function compileEscapedEchos(string $value): string
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

        $callback = function ($matches) {
            if ($matches[1]) {
                return substr($matches[0], 1);
            }

            if (preg_match('#^[\w.\[\]"\']+$#', $matches[2]) || preg_match('#^\\$\w+\(#', $matches[2])) {
                return $matches[0];
            } elseif ($this->isSafeEchos($matches[2])) {
                return "<?= $matches[2] ?>" . (empty($matches[3]) ? '' : $matches[3]);
            } else {
                return '<?= e(' . $this->compileEchoDefaults($matches[2]) . '); ?>' . (empty($matches[3]) ? ''
                        : $matches[3]);
            }
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    protected function isSafeEchos(string $value): bool
    {
        return preg_match('#^([a-z\d_]+)\\(#', $value, $match) === 1
            && in_array($match[1], $this->safe_functions, true);
    }

    protected function compileEchoDefaults(string $value): string
    {
        /** @noinspection RegExpUnnecessaryNonCapturingGroup */
        return preg_replace('/^(?=\\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
    }

    protected function compile_yield(string $expression): string
    {
        return "<?= \$renderer->getSection$expression; ?>";
    }

    protected function compile_section(string $expression): string
    {
        return "<?php \$renderer->startSection$expression; ?>";
    }

    protected function compile_append(): string
    {
        return '<?php $renderer->appendSection(); ?>';
    }

    protected function compile_endSection(): string
    {
        return '<?php $renderer->stopSection(); ?>';
    }

    protected function compile_stop(): string
    {
        return '<?php $renderer->stopSection(); ?>';
    }

    protected function compile_else(): string
    {
        return '<?php else: ?>';
    }

    protected function compile_for(string $expression): string
    {
        return "<?php for$expression: ?>";
    }

    protected function compile_foreach(string $expression): string
    {
        return "<?php \$index = -1; foreach$expression: \$index++; ?>";
    }

    protected function compile_foreachElse(): string
    {
        $this->foreachelse_used = true;
        return '<?php endforeach; ?> <?php if($index === -1): ?>';
    }

    protected function compile_can(string $expression): string
    {
        return "<?php if (container('ManaPHP\Http\AuthorizationInterface')->isAllowed$expression): ?>";
    }

    protected function compile_allow(string $expression): string
    {
        $parts = explode(',', substr($expression, 1, -1));
        $expr = $this->compileString($parts[1]);
        return "<?php if (container('ManaPHP\Http\AuthorizationInterface')->isAllowed($parts[0])): ?>$expr<?php endif ?>";
    }

    protected function compile_cannot(string $expression): string
    {
        return "<?php if (!container('ManaPHP\Http\AuthorizationInterface')->isAllowed$expression): ?>";
    }

    protected function compile_if(string $expression): string
    {
        return "<?php if$expression: ?>";
    }

    protected function compile_elseif(string $expression): string
    {
        return "<?php elseif$expression: ?>";
    }

    protected function compile_while(string $expression): string
    {
        return "<?php while$expression: ?>";
    }

    protected function compile_endWhile(): string
    {
        return '<?php endwhile; ?>';
    }

    protected function compile_endFor(): string
    {
        return '<?php endfor; ?>';
    }

    protected function compile_endForeach(): string
    {
        $r = $this->foreachelse_used ? '<?php endif; ?>' : '<?php endforeach; ?>';
        $this->foreachelse_used = false;
        return $r;
    }

    protected function compile_endCan(): string
    {
        return '<?php endif; ?>';
    }

    protected function compile_endCannot(): string
    {
        return '<?php endif; ?>';
    }

    protected function compile_endIf(): string
    {
        return '<?php endif; ?>';
    }

    protected function compile_include(string $expression): string
    {
        return "<?php \$renderer->partial$expression ?>";
    }

    protected function compile_partial(string $expression): string
    {
        return "<?php \$renderer->partial$expression ?>";
    }

    protected function compile_block(string $expression): string
    {
        return "<?php container('ManaPHP\Mvc\ViewInterface')->block$expression ?>";
    }

    protected function compile_break(string $expression): string
    {
        return $expression ? "<?php if$expression break; ?>" : '<?php break; ?>';
    }

    protected function compile_continue(string $expression): string
    {
        return $expression ? "<?php if$expression continue; ?>" : '<?php continue; ?>';
    }

    protected function compile_maxAge(string $expression): string
    {
        return "<?php container('ManaPHP\Mvc\ViewInterface')->setMaxAge$expression; ?>";
    }

    protected function compile_layout(string $expression): string
    {
        if (str_contains($expression, '(false)')) {
            return "<?php container('ManaPHP\Mvc\ViewInterface')->disableLayout(); ?>";
        } else {
            return "<?php container('ManaPHP\Mvc\ViewInterface')->setLayout$expression; ?>";
        }
    }

    protected function compile_content(): string
    {
        return "<?= container('ManaPHP\Mvc\ViewInterface')->getContent(); ?>";
    }

    protected function compile_php(string $expression): string
    {
        if ($expression[0] === '(') {
            $expression = substr($expression, 1, -1);
        }

        return $expression ? "<?php $expression; ?>" : '<?php ';
    }

    protected function compile_endPhp(): string
    {
        return ' ?>';
    }

    protected function compile_widget(string $expression): string
    {
        return "<?php container('ManaPHP\Mvc\ViewInterface')->widget$expression; ?>";
    }

    protected function compile_url(string $expression): string
    {
        if (strcspn($expression, '$\'"') === strlen($expression)) {
            $expression = '(\'' . trim($expression, '()') . '\')';
        }

        return "<?= url$expression; ?>";
    }

    protected function compile_asset(string $expression): string
    {
        if (strcspn($expression, '$\'"') === strlen($expression)) {
            $expression = '(\'' . trim($expression, '()') . '\')';
        }

        return asset(substr($expression, 2, -2));
        /*return "<?= asset{$expression}; ?>";*/
    }

    protected function compile_flash(): string
    {
        return "<?php container('ManaPHP\Mvc\View\FlashInterface')->output() ?>";
    }

    protected function compile_json(string $expression): string
    {
        $expression = substr($expression, 1, -1);
        return "<?= json_stringify($expression) ;?>";
    }

    protected function compile_debugger(): string
    {
        return '<?php if(container("ManaPHP\Http\ResponseInterface")->hasHeader("X-Debugger-Link")){?><div class="debugger"><a target="_self" href="'
            . '<?= container("ManaPHP\Http\ResponseInterface")->getHeader("X-Debugger-Link") ?>">Debugger</a></div><?php }?> ';
    }

    protected function compile_eol(): string
    {
        return '<?= PHP_EOL ?>';
    }

    protected function compile_date(string $expression): string
    {
        $time = substr($expression, 1, -1);
        return "<?= date('Y-m-d H:i:s', $time) ?>";
    }

    protected function compile_action(string $expression): string
    {
        if (preg_match('#^\\(([\'"]?)([/_a-z\d]+)\1\\)$#i', $expression, $match)) {
            return action($match[2]);
        } else {
            return "<?= action$expression ?>";
        }
    }

    protected function compile_use(string $expression): string
    {
        return '<?php use ' . substr($expression, 1, -1) . ';?>';
    }

    protected function compile_html(string $expression): string
    {
        return "<?= html$expression; ?>";
    }

    protected function compile_css(): string
    {
        return "<?php \$renderer->startSection('css'); ?>";
    }

    protected function compile_endcss(): string
    {
        return '<?php $renderer->appendSection(); ?>';
    }

    protected function compile_js(): string
    {
        return "<?php \$renderer->startSection('js'); ?>";
    }

    protected function compile_endjs(): string
    {
        return '<?php $renderer->appendSection(); ?>';
    }

    public function directive(string $name, callable $handler): static
    {
        $this->directives[$name] = $handler;

        return $this;
    }
}