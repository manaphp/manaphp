<?php
namespace ManaPHP\Counter\Engine\Db;

/**
 * Class ManaPHP\Counter\Engine\Db\Model
 *
 * @package counter\engine
 */
class Model extends \ManaPHP\Db\Model
{
    /**
     * @var string
     */
    public $hash;

    /**
     * @var string
     */
    public $key;

    /**
     * @var int
     */
    public $value;

    /**
     * @var int
     */
    public $created_time;

    /**
     * @var int
     */
    public $updated_time;

    public function getSource($context = null)
    {
        return 'manaphp_counter';
    }
}