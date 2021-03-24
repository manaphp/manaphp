<?php

namespace ManaPHP\Tracing;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class Tracer extends Component
{
    /**
     * @var bool
     */
    protected $verbose = false;

    public function __construct($options = [])
    {
        if (isset($options['verbose'])) {
            $this->verbose = (bool)$options['verbose'];
        }
    }

    /**
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    public function debug($message, $category = null)
    {
        $this->logger->debug($message, $category);
    }

    /**
     * Sends/Writes an info message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    public function info($message, $category = null)
    {
        $this->logger->info($message, $category);
    }

    /**
     * Sends/Writes a warning message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    public function warn($message, $category = null)
    {
        $this->logger->warn($message, $category);
    }

    /**
     * Sends/Writes an error message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    public function error($message, $category = null)
    {
        $this->logger->error($message, $category);
    }

    /**
     * Sends/Writes a critical message to the log
     *
     * @param string|array $message
     * @param string       $category
     *
     * @return void
     */
    public function fatal($message, $category = null)
    {
        $this->logger->fatal($message, $category);
    }
}