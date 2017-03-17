<?php
namespace ManaPHP;

use ManaPHP\Stopwatch\Exception as StopwatchException;

class Stopwatch extends Component implements StopwatchInterface
{
    protected $_recorders = [];

    /**
     * @param string $name
     *
     * @return static
     */
    public function start($name)
    {
        $this->_recorders[$name] = [];
        $this->_recorders[$name]['memory_start'] = memory_get_usage(true);
        $this->_recorders[$name]['time_start'] = microtime(true);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isStarted($name)
    {
        return isset($this->_recorders[$name]);
    }

    /**
     * @param string $name
     *
     * @return static
     * @throws \ManaPHP\Stopwatch\Exception
     */
    public function stop($name)
    {
        if (!isset($this->_recorders[$name])) {
            throw new StopwatchException('`:name` name is not started.', ['name' => $name]);
        }

        if (isset($this->_recorders[$name]['time_stop'])) {
            throw new StopwatchException('`:name` name is stopped.', ['name' => $name]);
        }

        $this->_recorders[$name]['time_stop'] = microtime(true);
        $this->_recorders[$name]['memory_stop'] = memory_get_usage(true);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isStopped($name)
    {
        return isset($this->_recorders[$name]['time_stop']);
    }

    /**
     * @param string $name
     *
     * @return float
     * @throws \ManaPHP\Stopwatch\Exception
     */
    public function getElapsedTime($name)
    {
        if (!isset($this->_recorders[$name])) {
            throw new StopwatchException('`:name` name is not started.', ['name' => $name]);
        }

        $start = $this->_recorders[$name]['time_start'];
        $stop = isset($this->_recorders[$name]['time_stop']) ? $this->_recorders[$name]['time_stop'] : microtime(true);

        return round($stop - $start, 4);
    }

    /**
     * @param string $name
     *
     * @return int
     * @throws \ManaPHP\Stopwatch\Exception
     */
    public function getUsedMemory($name)
    {
        if (!isset($this->_recorders[$name])) {
            throw new StopwatchException('`:name` name is not started.', ['name' => $name]);
        }

        $start = $this->_recorders[$name]['memory_start'];
        $stop = isset($this->_recorders[$name]['memory_stop']) ? $this->_recorders[$name]['memory_stop'] : microtime(true);

        return $stop - $start;
    }

    /**
     * @return array
     * @throws \ManaPHP\Stopwatch\Exception
     */
    public function dump()
    {
        $data = [];

        foreach ($this->_recorders as $name => $v) {
            $data[$name] = [
                'time' => $this->getElapsedTime($name),
                'memory' => $this->getUsedMemory($name)
            ];
        }

        return $data;
    }

    /**
     * @param int|int[]           $times
     * @param callable|callable[] $functions
     *
     * @return array
     */
    public function test($times, $functions)
    {
        $data = [];

        foreach ((array)$times as $t) {
            $item = [];
            foreach ((array)$functions as $name => $function) {
                $start = microtime(true);

                for ($i = 0; $i < $t; $i++) {
                    $function($t);
                }

                $item[$name] = round(microtime(true) - $start, 3);
            }

            $data[$t] = is_array($functions) ? $item : $item[0];
        }

        return $data;
    }
}