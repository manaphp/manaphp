<?php
namespace ManaPHP\Meter\Round;

/**
 * Class ManaPHP\Meter\Round\Model
 *
 * @package roundMeter
 */
class Model extends \ManaPHP\Mvc\Model
{
    /**
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $count;

    /**
     * @var int
     */
    public $begin_time;

    /**
     * @var int
     */
    public $duration;

    /**
     * @var int
     */
    public $created_time;

    public function getSource($context = null)
    {
        return 'manaphp_round_meter';
    }
}
