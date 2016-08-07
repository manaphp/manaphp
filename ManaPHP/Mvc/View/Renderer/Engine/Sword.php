<?php
namespace ManaPHP\Mvc\View\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Mvc\View\Renderer\EngineInterface;
use ManaPHP\Utility\File;
use ManaPHP\Utility\Text;

class Sword extends Component implements EngineInterface
{
    /**
     * All custom "directive" handlers.
     *
     * This was implemented as a more usable "extend" in 5.1.
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
     * Counter to keep track of nested forElse statements.
     *
     * @var int
     */
    protected $_forElseCounter = 0;

    /**
     * Compile the given Blade template contents.
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
     * Compile Blade comments into valid PHP.
     *
     * @param  string $value
     *
     * @return string
     */
    protected function _compileComments($value)
    {
        $pattern = sprintf('/%s--(.*?)--%s/s', $this->_escapedTags[0], $this->_escapedTags[1]);

        return preg_replace($pattern, '<?php /*$1*/ ?>', $value);
    }

    /**
     * Compile Blade echos into valid PHP.
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
     * Compile Blade statements that start with "@".
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
                $match[0] = call_user_func($this->_directives[$match[1]], $match[3]);
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

            return $matches[1] ? $matches[0] : '<?php echo $view->escape(' . $this->_compileEchoDefaults($matches[2]) . '); ?>' . $whitespace;
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
        return "<?php echo \$view->getSection{$expression}; ?>";
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
        return "<?php \$view->startSection{$expression}; ?>";
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
        return '<?php $view->appendSection(); ?>';
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
        return '<?php $view->stopSection(); ?>';
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
        return '<?php $view->stopSection(); ?>';
    }

    /**
     * Compile the overwrite statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileOverwrite(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php $view->stopSection(true); ?>';
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
        return "<?php foreach{$expression}: ?>";
    }

    /**
     * Compile the for else statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileForElse($expression)
    {
        $empty = '$__empty_' . ++$this->_forElseCounter;

        return "<?php {$empty} = true; foreach{$expression}: {$empty} = false; ?>";
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
     * Compile the empty statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileEmpty(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        $empty = '$__empty_' . $this->_forElseCounter--;

        return "<?php endforeach; if ({$empty}): ?>";
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
     * Compile the end-for-else statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileEndForElse(
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
        if (Text::startsWith($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return "<?php \$view->partial($expression); ?>";
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
     * Compile the stack statements into the content.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileStack($expression)
    {
        return "<?php echo \$view->getSection{$expression}; ?>";
    }

    /**
     * Compile the push statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compilePush($expression)
    {
        return "<?php \$view->startSection{$expression}; ?>";
    }

    /**
     * Compile the end push statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileEndPush(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php $view->appendSection(); ?>';
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileBreak(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php break;?>';
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileContinue(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php continue;?>';
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
        return "<?php echo \$view->getContent(); ?>";
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compilePhp(
        /** @noinspection PhpUnusedParameterInspection */
        $expression
    ) {
        return '<?php ';
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
     * Compile the widget statements into valid PHP.
     *
     * @param  string $expression
     *
     * @return string
     */
    protected function _compileUrl($expression)
    {
        return "<?php echo \$di->url->get{$expression}; ?>";
    }

    /**
     * Register a handler for custom directives.
     *
     * @param  string   $name
     * @param  callable $handler
     *
     * @return void
     */
    public function directive($name, callable $handler)
    {
        $this->_directives[$name] = $handler;
    }

    public function render($file, $vars = [])
    {
        $_compiledFile = $this->alias->resolve('@data/Sword/' . str_replace($this->alias->get('@app'), '', $file));

        if ($this->configure->debug || !file_exists($_compiledFile) || filemtime($file) > filemtime($_compiledFile)) {
            File::setContent($_compiledFile, $this->compileString(File::getContent($file)));
        }

        extract($vars, EXTR_SKIP);

        /** @noinspection PhpIncludeInspection */
        require $_compiledFile;
    }
}