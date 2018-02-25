<?php
namespace ManaPHP\Renderer\Engine\Sword;

use ManaPHP\Component;
use ManaPHP\Renderer\Engine\Sword\Exception as SwordException;

/**
 * Class ManaPHP\Renderer\Engine\Sword
 *
 * @package renderer\engine
 *
 * @property \ManaPHP\Mvc\UrlInterface    $url
 * @property \ManaPHP\Mvc\RouterInterface $router
 */
class Compiler extends Component
{
    /**
     * All custom "directive" handlers.
     *
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
     * Compile the given Sword template contents.
     *
     * @param  string $value
     *
     * @return string
     */
    public function compileString($value)
    {
        $result = '';

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
            if (method_exists($this, $method = '_compile' . ucfirst($match[1]))) {
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
            $whitespace = empty($matches[3]) ? '' : $matches[3];

            return $matches[1] ? substr($matches[0], 1) : '<?php echo $renderer->escape(' . $this->_compileEchoDefaults($matches[2]) . '); ?>' . $whitespace;
        };

        return preg_replace_callback($pattern, $callback, $value);
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
    protected function _compileYield($expression)
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
    protected function _compileSection($expression)
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
    protected function _compileAppend(
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
    protected function _compileEndSection(
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
    protected function _compileStop(
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
    protected function _compileElse(
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
    protected function _compileFor($expression)
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
    protected function _compileForeach($expression)
    {
        return "<?php \$index = -1; foreach{$expression}: \$index++; ?>";
    }

    /**
     * Compile the can statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileCan($expression)
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
    protected function _compileAllow($expression)
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
    protected function _compileCannot($expression)
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
    protected function _compileIf($expression)
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
    protected function _compileElseIf($expression)
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
    protected function _compileWhile($expression)
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
    protected function _compileEndWhile(
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
    protected function _compileEndFor(
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
    protected function _compileEndForeach(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php endforeach; ?>';
    }

    /**
     * Compile the end-can statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileEndCan(
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
    protected function _compileEndCannot(
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
    protected function _compileEndIf(
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
    protected function _compileInclude($expression)
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
    protected function _compilePartial($expression)
    {
        return $this->_compileInclude($expression);
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileBreak($expression)
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
    protected function _compileContinue($expression)
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
    protected function _compileLayout($expression)
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
    protected function _compileContent(
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
    protected function _compilePhp($expression)
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
    protected function _compileEndPhp(
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
    protected function _compileWidget($expression)
    {
        return "<?php echo \$view->widget{$expression}; ?>";
    }

    /**
     * Compile the Url statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileUrl($expression)
    {
        if (strcspn($expression, '$\'"') === strlen($expression)) {
            $expression = '(\'' . trim($expression, '()') . '\')';
        }

        return "<?php echo \$url->get{$expression}; ?>";
    }

    /**
     * Compile the Asset statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileAsset($expression)
    {
        if (strcspn($expression, '$\'"') === strlen($expression)) {
            $expression = '(\'' . trim($expression, '()') . '\')';
        }

        return $this->url->getAsset(substr($expression, 2, -2));
        /* return "<?php echo \$url->getAsset{$expression }; ?>";*/
    }

    /**
     * Compile the Asset statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileFlash(
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
    protected function _compileJson($expression)
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
    protected function _compileDebugger(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        /** @noinspection HtmlUnknownTarget */
        return '<?php if($di->configure->debug){?><div class="debugger"><a target="_self" href="<?php echo $di->debugger->getUrl(); ?>">Debugger</a></div><?php }?> ';
    }

    /**
     * Compile the json statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileCsrfToken(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php echo $di->csrfToken->get(); ?>';
    }

    /**
     * Compile the json statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compilePagination($expression)
    {
        return "<?php echo \$di->paginator->renderAsHtml{$expression}; ?>";
    }

    /**
     * Compile the json statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileTranslate($expression)
    {
        return "<?php echo \$di->translation->translate{$expression}; ?>";
    }

    /**
     * Compile the eol statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileEol(
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
     * @throws \ManaPHP\Renderer\Engine\Sword\Exception
     */
    protected function _compileRequest(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        $parts = explode(',', substr($expression, 1, -1));

        switch (count($parts)) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 1:
                $parts[] = "''";
            case 2:
                return "<?php if(isset(\$_REQUEST[$parts[0]])) echo \$renderer->escape(\$_REQUEST[$parts[0]]); else echo $parts[1]; ?>";
            default:
                throw new SwordException(['bad expression: `:expression`', 'expression' => "@request($expression)"]);
        }
    }

    /**
     * Compile the eol statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileDate(
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
    protected function _compileAction($expression)
    {
        if (preg_match('#^\\(([/_a-z\d]+)\\)$#i', $expression, $match)) {
            return $this->router->createActionUrl($match[1]);
        } else {
            return "<?php echo \$this->router->createActionUrl{$expression} ?>";
        }
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