<?php

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;

/**
 * @property-read \ManaPHP\ConfigInterface             $config
 * @property-read \ManaPHP\Debugging\DebuggerInterface $debugger
 */
class DebuggerBootstrapper extends Component implements BootstrapperInterface
{
    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['enabled'])) {
            $this->enabled = (bool)$options['enabled'];
        } else {
            $this->enabled = in_array($this->config->get('env'), ['dev', 'test']);
        }
    }

    public function bootstrap()
    {
        if ($this->enabled) {
            $this->debugger->start();
        }
    }
}