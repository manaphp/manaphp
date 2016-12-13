<?php
namespace ManaPHP\I18n;

use ManaPHP\Component;

/**
 * Class ManaPHP\Message\Translation
 *
 * @package i18n
 *
 * @property \ManaPHP\Http\RequestInterface   $request
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 */
class Translation extends Component implements TranslationInterface
{
    /**
     * @var array
     */
    protected $_files = [];

    /**
     * @var array
     */
    protected $_messages = [];

    /**
     * @var string
     */
    protected $_language;

    /**
     * Translation constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $files = isset($options['files']) ? $options['files'] : ['@messages/:language/' . 'Module.php', '@messages/:language/' . $this->dispatcher->getControllerName() . '.php'];

        $this->_language = isset($options['language']) ? preg_replace('#\s*#', '', $options['language']) : 'en';

        $languages = array_reverse(explode(',', $this->_language));

        /** @noinspection ForeachSourceInspection */
        foreach ($files as $file) {
            foreach ($languages as $language) {
                $f = strtr($file, [':language' => $language]);
                if ($this->filesystem->fileExists($f)) {
                    /** @noinspection PhpIncludeInspection */
                    $messages = require $this->alias->resolve($f);
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $this->_messages = array_merge($this->_messages, $messages);

                    $this->_files[] = $f;
                }
            }
        }
    }

    /**
     * @param string $messageId
     * @param array  $bind
     *
     * @return string
     */
    public function translate($messageId, $bind = [])
    {
        $message = isset($this->_messages[$messageId]) ? $this->_messages[$messageId] : $messageId;

        if (count($bind) !== 0) {
            $replaces = [];

            foreach ($bind as $k => $v) {
                $replaces[':' . $k] = $v;
            }

            return strtr($message, $replaces);
        } else {
            return $message;
        }
    }
}