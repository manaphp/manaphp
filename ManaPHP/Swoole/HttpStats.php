<?php
namespace ManaPHP\Swoole;

use ManaPHP\Component;

/**
 * Class ManaPHP\Swoole\HttpStats
 *
 * @package ManaPHP\Swoole
 * @property \ManaPHP\Http\RequestInterface  $request
 * @property \ManaPHP\Http\ResponseInterface $response
 */
class HttpStats extends Component
{
    /**
     * @var \swoole_http_server
     */
    protected $_swoole;

    /**
     * @var int
     */
    protected $_worker_num;

    /**
     * @var int
     */
    protected $_max_listen_queue = 0;

    /**
     * @var int
     */
    protected $_max_active_processes;

    /**
     * @var int
     */
    protected $_stats_ready;

    /**
     * @var array
     */
    protected $_stats;

    /**
     * @var [][]
     */
    protected $_workers_stats;

    const MSG_STATS_REQUEST = 'httpStats.request';
    const MSG_STATS_RESPONSE = 'httpStats.response';

    /**
     * Stats constructor.
     *
     * @param array $options
     */
    public function __construct($options)
    {
        $this->_swoole = $options['swoole'];
        $this->_worker_num = $this->_swoole->setting['worker_num'];
        $this->_swoole->on('pipeMessage', [$this, 'onPipeMessage']);

        $this->_stats['state'] = 0;

        $this->_workers_stats = array_fill(0, $this->_worker_num, []);
    }

    public function onPipeMessage($server, $src_worker_id, $data)
    {
        $msg = json_decode($data, true);
        if ($msg['type'] === self::MSG_STATS_REQUEST) {
            $this->_swoole->sendMessage(json_encode(['type' => self::MSG_STATS_RESPONSE, 'data' => $this->_getSelfStats()]), $src_worker_id);
        } elseif ($msg['type'] === self::MSG_STATS_RESPONSE) {
            $this->_stats_ready++;
            $this->_workers_stats[$src_worker_id] = $msg['data'];
        }
    }

    /**
     * @return array
     */
    protected function _getSelfStats()
    {
        $stats = $this->_stats;
        $swoole_stats = $this->_swoole->stats();
        $stats['start time'] = $swoole_stats['start_time'];

        return [
            'pid' => $this->_swoole->worker_pid,
            'state' => $stats['state'] === 1 ? 'Running' : 'Idle',
            'start time' => date('d/M/Y:H:i:s O', $stats['start time']),
            'start since' => time() - $stats['start time'],
            'requests' => isset($stats['requests']) ? $stats['requests'] : 0,
            'request duration' => isset($stats['request duration']) ? $stats['request duration'] : 0,
            'request method' => isset($stats['request method']) ? $stats['request method'] : '',
            'request URI' => isset($stats['request URI']) ? $stats['request URI'] : '',
            'content length' => isset($stats['content length']) ? $stats['content length'] : 0,
            'user' => get_current_user(),
            'script' => isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '',
            'last request cpu' => sys_getloadavg()[0],
            'last request memory' => isset($stats['last request memory']) ? $stats['last request memory'] : 0
        ];
    }

    /**
     * @return array[]
     */
    public function getWorkersStats()
    {
        $this->_stats_ready = 0;
        for ($work_id = 0; $work_id <= $this->_worker_num; $work_id++) {
            if ($work_id === $this->_swoole->worker_id) {
                $this->_workers_stats[$work_id] = $this->_getSelfStats();
                $this->_stats_ready++;
            } else {
                $this->_swoole->sendMessage(json_encode(['type' => self::MSG_STATS_REQUEST]), $work_id);
            }
        }

        $start_time = microtime(true);
        while ($this->_stats_ready !== $this->_worker_num + 1 && microtime(true) - $start_time < 0.1) {
            usleep(100);
        }

        return $this->_workers_stats;
    }

    public function onBeforeRequest()
    {
        $stats = $this->_swoole->stats();
        $this->_stats['state'] = 1;
        $this->_stats['requests'] = $stats['worker_request_count'];
        $this->_stats['start time'] = $stats['start_time'];
        $this->_stats['request duration'] = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000000;
        $this->_stats['request method'] = $_SERVER['REQUEST_METHOD'];
        $this->_stats['request URI'] = $_SERVER['REQUEST_URI'];
        $this->_stats['content length'] = isset($_SERVER['HTTP_CONTENT_LENGTH']) ? $_SERVER['HTTP_CONTENT_LENGTH'] : 0;
        $this->_stats['last request memory'] = memory_get_usage(true);
    }

    public function onAfterRequest()
    {
        $this->_stats['state'] = 0;
        $this->_stats['request duration'] = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000000;
        $this->_stats['last request memory'] = memory_get_usage(true);
    }

    public function handle()
    {
        $stats = $this->_swoole->stats();
        $setting = $this->_swoole->setting;
        $setting += ['worker_num' => 1];

        $data = [];
        $data['pool'] = 'www';
        $data['process manager'] = 'swoole';
        $data['start time'] = $stats['start_time'];
        $data['start since'] = time() - $stats['start_time'];
        $data['accepted conn'] = $stats['request_count'];
        $data['listen queue'] = max(0, $stats['connection_num'] - $setting['worker_num']);
        $this->_max_listen_queue = max($this->_max_listen_queue, $data['listen queue']);
        $data['max listen queue'] = $this->_max_listen_queue;
        $data['listen queue len'] = 0;
        $data['idle processes'] = $setting['worker_num'] - ($stats['connection_num'] > $setting['worker_num'] ? $setting['worker_num'] : $stats['connection_num']);
        $data['active processes'] = $stats['connection_num'] > $setting['worker_num'] ? $setting['worker_num'] : $stats['connection_num'];
        $this->_max_active_processes = max($this->_max_active_processes, $data['active processes']);
        $data['total processes'] = $setting['worker_num'];
        $data['max active processes'] = $this->_max_active_processes;
        $data['max children reached'] = 0;
        $data['slow requests'] = 0;

        $worker_stats = $this->request->has('full') ? $this->getWorkersStats() : null;

        if ($this->request->has('json')) {
            $new_data = [];
            foreach ($data as $k => $v) {
                $new_data[str_replace(' ', '-', $k)] = $v;
            }
            return $this->response->setJsonContent($new_data);
        } elseif ($this->request->has('xml')) {
            return $this->response->setXmlContent($data);
        } else {
            $data['start time'] = date('d/M/Y:H:i:s O', $stats['start_time']);
            $str = '';
            foreach ($data as $k => $v) {
                $str .= sprintf('%-22s%s', $k . ':', $v);
                $str .= "\r\n";
            }

            if (is_array($worker_stats)) {
                foreach ($worker_stats as $worker_stat) {
                    $str .= "\r\n";
                    $str .= str_repeat('*', 45);
                    $str .= "\r\n";
                    foreach ($worker_stat as $k => $v) {
                        $str .= sprintf('%-22s%s', $k . ':', $v);
                        $str .= "\r\n";
                    }
                }
            }
            $this->response->setContentType('Content-Type', 'text/plain;charset=UTF-8');
            return $this->response->setContent($str);
        }
    }
}