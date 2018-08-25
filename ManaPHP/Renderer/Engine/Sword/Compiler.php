<?php
namespace ManaPHP\Renderer\Engine\Sword;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\RuntimeException;

/**
 * Class ManaPHP\Renderer\Engine\Sword
 *
 * @package renderer\engine
 *
 * @property \ManaPHP\View\UrlInterface $url
 * @property \ManaPHP\RouterInterface   $router
 */
class Compiler extends Component
{
    /**
     * All custom "directive" handlers.
     *
     * @var array
     */
    protected $_directives = [];

    /**
     * Array of opening and closing tags for raw echos.
     *
     * @var array
     */
    protected $_rawTags = ['{!!', '!!}'];

    /**
     * Array of opening and closing tags for escaped echos.
     *
     * @var array
     */
    protected $_escapedTags = ['{{', '}}'];

    /**
     * @var bool
     */
    protected $_foreachelse_used = false;

    /**
     * @var array
     */
    protected $_safe_functions = [
        'e',
        'url',
        'action',
        'asset',
        'csrf_token',
        'csrf_field',
        'date',
        'html',
        'bundle',
        'attr_nv',
        'attr_inv',
        'widget',
        'partial',
        'block',
        'pager'
    ];

    /**
     * Compile the given Sword template contents.
     *
     * @param  string $value
     *
     * @return string
     */
    public function compileString($value)
    {
        $result = '';
        $value = $this->_replaceLinks($value);

        // Here we will loop through all of the tokens returned by the Zend lexer and
        // parse each one into the corresponding valid PHP. We will then have this
        // template as the correctly rendered PHP that can be rendered natively.
        foreach (token_get_all($value) as $token) {
            if (is_array($token)) {
                list($id, $content) = $token;
                if ($id === T_INLINE_HTML) {
                    $content = $this->_compileStatements($content);
                    $content = $this->_compileComments($content);
                    $content = $this->_compileEchos($content);
                }
            } else {
                $content = $token;
            }

            $result .= $content;
        }

        return $result;
    }

    /**
     * @param string $str
     *
     * @return string
     */
    protected function _replaceLinks($str)
    {
        //   return $str;
        return preg_replace_callback('#\s(href|src)="(/[^/][^":<{*?$]+\.(css|js|jpg|png|gif))"#',
            function ($match) {
                $hash = $match[2] . '?v=' . substr(md5_file(path("@public{$match[2]}")), 0, 12);
                return " $match[1]=\"<?php echo asset('$hash'); ?>\"";
            },
            $str);
    }

    /**
     * @param string $source
     * @param string $compiled
     *
     * @return static
     */
    public function compileFile($source, $compiled)
    {
        $source = $this->alias->resolve($source);
        $compiled = $this->alias->resolve($compiled);

        $dir = dirname($compiled);

        /** @noinspection NotOptimalIfConditionsInspection */
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException(['create `:dir` directory failed: :last_error_message', 'dir' => $dir]);
        }

        if (($str = file_get_contents($source)) === false) {
            throw new InvalidArgumentException(['read `:file` sword source file failed: :last_error_message', 'file' => $source]);
        }

        if (file_put_contents($compiled, $this->compileString($str), LOCK_EX) === false) {
            throw new RuntimeException(['write `:compiled` compiled file for `:source` file failed: :last_error_message',
                'complied' => $compiled,
                'source' => $source]);
        }

        return $this;
    }

    /**
     * Compile Sword comments into valid PHP.
     *
     * @param  string $value
     *
     * @return string
     */
    protected function _compileComments($value)
    {
        $pattern = sprintf('/%s--(.*?)--%s/s', $this->_escapedTags[0], $this->_escapedTags[1]);

        return preg_replace($pattern, '<?php /*$1*/ ?> ', $value);
    }

    /**
     * Compile Sword echos into valid PHP.
     *
     * @param  string $value
     *
     * @return string
     */
    protected function _compileEchos($value)
    {
        foreach ($this->_getEchoMethods() as $method => $length) {
            $value = $this->$method($value);
        }

        return $value;
    }

    /**
     * Get the echo methods in the proper order for compilation.
     *
     * @return array
     */
    protected function _getEchoMethods()
    {
        $methods = [
            '_compileRawEchos' => strlen(stripcslashes($this->_rawTags[0])),
            '_compileEscapedEchos' => strlen(stripcslashes($this->_escapedTags[0])),
        ];

        uksort($methods, function ($method1, $method2) use ($methods) {
            // Ensure the longest tags are processed first
            if ($methods[$method1] > $methods[$method2]) {
                return -1;
            }
            if ($methods[$method1] < $methods[$method2]) {
                return 1;
            }

            // Otherwise give preference to raw tags (assuming they've overridden)
            if ($method1 === '_compileRawEchos') {
                return -1;
            }
            if ($method2 === '_compileRawEchos') {
                return 1;
            }

            if ($method1 === '_compileEscapedEchos') {
                return -1;
            }
            if ($method2 === '_compileEscapedEchos') {
                return 1;
            }

            return 0;
        });

        return $methods;
    }

    /**
     * Compile Sword statements that start with "@".
     *
     * @param  string $value
     *
     * @return mixed
     */
    protected function _compileStatements($value)
    {
        $callback = function ($match) {
            if (method_exists($this, $method = '_compile_' . $match[1])) {
                $match[0] = $this->$method(isset($match[3]) ? $match[3] : null);
            } elseif (isset($this->_directives[$match[1]])) {
                $match[0] = call_user_func($this->_directives[$match[1]], isset($match[3]) ? $match[3] : null);
            }

            return isset($match[3]) ? $match[0] : $match[0] . $match[2];
        };

        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $callback, $value);
    }

    /**
     * Compile the "raw" echo statements.
     *
     * @param  string $value
     *
     * @return string
     */
    protected function _compileRawEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->_rawTags[0], $this->_rawTags[1]);

        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3];

            return $matches[1] ? substr($matches[0], 1) : '<?php echo ' . $this->_compileEchoDefaults($matches[2]) . '; ?>' . $whitespace;
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the escaped echo statements.
     *
     * @param  string $value
     *
     * @return string
     */
    protected function _compileEscapedEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->_escapedTags[0], $this->_escapedTags[1]);

        $callback = function ($matches) {
            if ($matches[1]) {
                return substr($matches[0], 1);
            }

            if (preg_match('#^[\w\.\[\]"\']+$#', $matches[2]) || preg_match('#^\\$[\w]+\(#', $matches[2])) {
                return $matches[0];
            } else {
                if ($this->_isSafeEchos($matches[2])) {
                    return "<?php echo $matches[2] ?>" . (empty($matches[3]) ? '' : $matches[3]);
                } else {
                    return '<?php echo e(' . $this->_compileEchoDefaults($matches[2]) . '); ?>' . (empty($matches[3]) ? '' : $matches[3]);
                }
            }
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    protected function _isSafeEchos($value)
    {
        return preg_match('#^([a-z\d_]+)\\(#', $value, $match) === 1 && in_array($match[1], $this->_safe_functions, true);
    }

    /**
     * Compile the default values for the echo statement.
     *
     * @param  string $value
     *
     * @return string
     */
    private function _compileEchoDefaults($value)
    {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
    }

    /**
     * Compile the yield statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_yield($expression)
    {
        return "<?php echo \$renderer->getSection{$expression}; ?>";
    }

    /**
     * Compile the section statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_section($expression)
    {
        return "<?php \$renderer->startSection{$expression}; ?>";
    }

    /**
     * Compile the append statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_append(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php $renderer->appendSection(); ?>';
    }

    /**
     * Compile the end-section statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_endSection(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php $renderer->stopSection(); ?>';
    }

    /**
     * Compile the stop statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_stop(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php $renderer->stopSection(); ?>';
    }

    /**
     * Compile the else statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_else(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php else: ?>';
    }

    /**
     * Compile the for statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_for($expression)
    {
        return "<?php for{$expression}: ?>";
    }

    /**
     * Compile the foreach statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_foreach($expression)
    {
        return "<?php \$index = -1; foreach{$expression}: \$index++; ?>";
    }

    /**
     * Compile the foreachelse statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_foreachElse($expression)
    {
        $this->_foreachelse_used = true;
        return "<?php endforeach; ?> <?php if(\$index === -1): ?>";
    }

    /**
     * Compile the can statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_can($expression)
    {
        return "<?php if (\$di->authorization->isAllowed{$expression}): ?>";
    }

    /**
     * Compile the allow statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_allow($expression)
    {
        $parts = explode(',', substr($expression, 1, -1));
        $expr = $this->compileString($parts[1]);
        return "<?php if (\$di->authorization->isAllowed($parts[0])): ?>$expr<?php endif ?>";
    }

    /**
     * Compile the cannot statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_cannot($expression)
    {
        return "<?php if (!\$di->authorization->isAllowed{$expression}): ?>";
    }

    /**
     * Compile the if statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_if($expression)
    {
        return "<?php if{$expression}: ?>";
    }

    /**
     * Compile the else-if statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_elseif($expression)
    {
        return "<?php elseif{$expression}: ?>";
    }

    /**
     * Compile the while statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_while($expression)
    {
        return "<?php while{$expression}: ?>";
    }

    /**
     * Compile the end-while statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_endWhile(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php endwhile; ?>';
    }

    /**
     * Compile the end-for statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_endFor(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php endfor; ?>';
    }

    /**
     * Compile the end-for-each statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_endForeach(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    )
    {
        $r = $this->_foreachelse_used ? '<?php endif; ?>' : '<?php endforeach; ?>';
        $this->_foreachelse_used = false;
        return $r;
    }

    /**
     * Compile the end-can statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_endCan(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-cannot statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_endCannot(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-if statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_endIf(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php endif; ?>';
    }

    /**
     * Compile the include statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_include($expression)
    {
        return "<?php \$renderer->partial{$expression} ?>";
    }

    /**
     * Compile the partial statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_partial($expression)
    {
        return "<?php partial{$expression} ?>";
    }

    /**
     * Compile the block statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_block($expression)
    {
        return "<?php block{$expression} ?>";
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_break($expression)
    {
        return $expression ? "<?php if{$expression} break; ?>" : '<?php break; ?>';
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_continue($expression)
    {
        return $expression ? "<?php if{$expression} continue; ?>" : '<?php continue; ?>';
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_layout($expression)
    {
        return "<?php \$view->setLayout{$expression}; ?>";
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_content(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php echo $view->getContent(); ?>';
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_php($expression)
    {
        if ($expression[0] === '(') {
            $expression = (string)substr($expression, 1, -1);
        }

        return $expression ? "<?php {$expression}; ?>" : '<?php ';
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_endPhp(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return ' ?>';
    }

    /**
     * Compile the widget statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_widget($expression)
    {
        return "<?php echo widget{$expression}; ?>";
    }

    /**
     * Compile the Url statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_url($expression)
    {
        if (strcspn($expression, '$\'"') === strlen($expression)) {
            $expression = '(\'' . trim($expression, '()') . '\')';
        }

        return "<?php echo url{$expression}; ?>";
    }

    /**
     * Compile the Asset statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_flash(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php $di->flash->output() ?>';
    }

    /**
     * Compile the json statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_json($expression)
    {
        $expression = (string)substr($expression, 1, -1);
        return "<?php echo json_encode({$expression}, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ;?>";
    }

    /**
     * Compile the json statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_debugger(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php if($di->configure->debug){?><div class="debugger"><a target="_self" href="<?php echo $di->debugger->getUrl(); ?>">Debugger</a></div><?php }?> ';
    }

    /**
     * Compile the pager statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_pager($expression)
    {
        return "<?php echo pager{$expression}; ?>";
    }

    /**
     * Compile the eol statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_eol(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php echo PHP_EOL ?>';
    }

    /**
     * Compile the eol statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_date(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        $time = substr($expression, 1, -1);
        return "<?php echo date('Y-m-d H:i:s', $time) ?>";
    }

    /**
     * Compile the action statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compile_action($expression)
    {
        if (preg_match('#^\\(([\'"]?)([/_a-z\d]+)\1\\)$#i', $expression, $match)) {
            return action($match[2]);
        } else {
            return "<?php echo action{$expression} ?>";
        }
    }

    /**
     * @param string $expression
     *
     * @return string
     */
    protected function _compile_use($expression)
    {
        return '<?php use ' . substr($expression, 1, -1) . ';?>';
    }

    /**
     * @param string $expression
     *
     * @return string
     */
    protected function _compile_constants($expression)
    {
        return "<?php echo json_encode(constants{$expression}, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), PHP_EOL; ?>";
    }

    /**
     * @param string $expression
     *
     * @return string
     */
    protected function _compile_html($expression)
    {
        return "<?php echo html{$expression}; ?>";
    }

    /**
     * @param string $expression
     *
     * @return string
     */
    protected function _compile_css($expression)
    {
        return "<?php \$renderer->startSection('css'); ?>";
    }

    /**
     * @param string $expression
     *
     * @return string
     */
    protected function _compile_endcss($expression)
    {
        return '<?php $renderer->appendSection(); ?>';
    }

    /**
     * @param string $expression
     *
     * @return string
     */
    protected function _compile_js($expression)
    {
        return "<?php \$renderer->startSection('js'); ?>";
    }

    /**
     * @param string $expression
     *
     * @return string
     */
    protected function _compile_endjs($expression)
    {
        return '<?php $renderer->appendSection(); ?>';
    }

    /**
     * Register a handler for custom directives.
     *
     * @param  string   $name
     * @param  callable $handler
     *
     * @return static
     */
    public function directive($name, callable $handler)
    {
        $this->_directives[$name] = $handler;

        return $this;
    }
}