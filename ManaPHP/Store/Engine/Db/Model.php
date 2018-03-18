<?php
namespace ManaPHP\Store\Engine\Db;

/**
 * Class ManaPHP\Store\Engine\Db\Model
 *
 * @package store\engine
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
     * @var string
     */
    public $value;

    /**
     * @var int
     */
    public $updated_time;

    public function getSource($context = null)
    {
        return 'manaphp_store';
    }
}