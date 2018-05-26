<?php

namespace ManaPHP\Db\Adapter;

use ManaPHP\Component;
use ManaPHP\DbInterface;
use ManaPHP\Exception\RuntimeException;

class Proxy extends Component implements DbInterface
{
    /**
     * @var array
     */
    protected $_masters = [];

    /**
     * @var array
     */
    protected $_slaves = [];

    /**
     * @var \ManaPHP\Db\Adapter\Proxy
     */
    protected $_masterConnection;

    /**
     * @var \ManaPHP\Db\Adapter\Proxy
     */
    protected $_slaveConnection;

    /**
     * @var \ManaPHP\Db\Adapter\Proxy
     */
    protected $_currentConnection;

    /**
     * @var int
     */
    protected $_transactionLevel = 0;

    /**
     * Proxy constructor.
     *
     * @param array[] $options
     */
    public function __construct($options)
    {
        $masters = [];
        if (is_string($options['masters'])) {
            $masters[] = ['options' => $options['masters'], 'weight' => 1];
        } else {
            foreach ($options['masters'] as $master) {
                if (is_string($master)) {
                    $masters[] = ['options' => $master, 'weight' => 1];
                } else {
                    $masters[] = $master;
                }
            }
        }
        $this->_masters = $masters;

        $slaves = [];
        if (is_string($options['slaves'])) {
            $slaves[] = ['options' => $options['slave'], 'weight' => 1];
        } else {
            foreach ($options['slaves'] as $slave) {
                if (is_string($slave)) {
                    $slaves[] = ['options' => $slave, 'weight' => 1];
                } else {
                    $slaves[] = $slave;
                }
            }
        }
        $this->_slaves = $slaves;
    }

    public function ping()
    {
        if ($this->_currentConnection !== null) {
            return $this->_currentConnection->ping();
        } else {
            return true;
        }
    }

    /**
     * @param array $ar
     *
     * @return array|false
     */
    protected function _selectDbConfig($ar)
    {
        if (count($ar) <= 1) {
            return reset($ar);
        }

        $sum = 0;
        foreach ($ar as $item) {
            $sum += $item['weight'];
        }

        $r = mt_rand(0, $sum);

        foreach ($ar as $k => $item) {
            $weight = $item['weight'];
            if ($weight === 0) {
                continue;
            }

            if ($weight <= $r) {
                return $ar[$k];
            }

            $r -= $weight;
        }

        return $ar[0];
    }

    /**
     * @return static
     */
    public function getMasterConnection()
    {
        if ($this->_masterConnection === null) {
            $master = $this->_selectDbConfig($this->_masters);

            if ($master === false) {
                throw new RuntimeException('there is no available master server.');
            }
            $options = $master['options'];

            $this->fireEvent('db:createMaster', ['options' => $options]);

            $this->_masterConnection = new Mysql($options);
        }

        if ($this->_currentConnection !== $this->_masterConnection) {
            $this->fireEvent('db:switchToMaster');
        }

        return $this->_currentConnection = $this->_masterConnection;
    }

    /**
     * @return static
     */
    public function getSlaveConnection()
    {
        if ($this->_slaveConnection === null) {
            $slave = $this->_selectDbConfig($this->_slaves);
            if ($slave === false) {
                throw new RuntimeException('there is no available slave server.');
            }

            $options = $slave['options'];

            $this->fireEvent('db:createSlave', ['dsn' => $options]);

            $this->_slaveConnection = new Mysql($options);
        }

        if ($this->_currentConnection !== $this->_slaveConnection) {
            $this->fireEvent('db:switchToSlave');
        }

        return $this->_currentConnection = $this->_slaveConnection;
    }

    /**
     * @return static
     */
    public function getCurrentConnection()
    {
        return $this->_currentConnection;
    }

    /**
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     *
     * @return \PDOStatement
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function query($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC)
    {
        if ($this->isUnderTransaction()) {
            return $this->getMasterConnection()->query($sql, $bind, $fetchMode);
        } else {
            return $this->getSlaveConnection()->query($sql, $bind, $fetchMode);
        }
    }

    /**
     * @return \ManaPHP\Db\QueryInterface
     */
    public function createQuery()
    {
        return $this->_di->get('ManaPHP\Db\Query', [$this]);
    }

    /**
     * @param string $sql
     * @param array  $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function execute($sql, $bind = [])
    {
        return $this->getMasterConnection()->execute($sql, $bind);
    }

    /**
     * @return int
     */
    public function affectedRows()
    {
        return $this->_currentConnection->affectedRows();
    }

    /**
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     *
     * @return array|false
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function fetchOne($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC)
    {
        if ($this->isUnderTransaction()) {
            return $this->getMasterConnection()->fetchOne($sql, $bind, $fetchMode);
        } else {
            return $this->getSlaveConnection()->fetchOne($sql, $bind, $fetchMode);
        }
    }

    /**
     * @param string $sql
     * @param array  $bind
     * @param int    $fetchMode
     * @param null   $indexBy
     *
     * @return array
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function fetchAll($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC, $indexBy = null)
    {
        if ($this->isUnderTransaction()) {
            return $this->getMasterConnection()->fetchAll($sql, $bind, $fetchMode, $indexBy);
        } else {
            return $this->getSlaveConnection()->fetchAll($sql, $bind, $fetchMode, $indexBy);
        }
    }

    /**
     * @param string $table
     * @param array  $fieldValues
     *
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function insert($table, $fieldValues)
    {
        $this->getMasterConnection()->insert($table, $fieldValues);
    }

    /**
     * @param string       $table
     * @param array        $fieldValues
     * @param array|string $conditions
     * @param array        $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function update($table, $fieldValues, $conditions, $bind = [])
    {
        return $this->getMasterConnection()->update($table, $fieldValues, $conditions, $bind);
    }

    /**
     * @param string       $table
     * @param array|string $conditions
     * @param array        $bind
     *
     * @return int
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function delete($table, $conditions, $bind = [])
    {
        return $this->getMasterConnection()->delete($table, $conditions, $bind);
    }

    public function getSQL()
    {
        return $this->_currentConnection->getSQL();
    }

    public function getEmulatedSQL($preservedStrLength = -1)
    {
        return $this->_currentConnection->getEmulatedSQL($preservedStrLength);
    }

    public function getBind()
    {
        return $this->_currentConnection->getBind();
    }

    /**
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function begin()
    {
        $this->getMasterConnection()->begin();

        $this->_transactionLevel++;
    }

    /**
     * @return bool
     */
    public function isUnderTransaction()
    {
        return $this->_transactionLevel > 0;
    }

    /**
     * @return void
     */
    public function rollback()
    {
        $this->_currentConnection->rollback();

        $this->_transactionLevel--;
    }

    /**
     * @return void
     */
    public function commit()
    {
        $this->_currentConnection->commit();

        $this->_transactionLevel--;
    }

    /**
     * @return int
     */
    public function lastInsertId()
    {
        return $this->_currentConnection->lastInsertId();
    }

    /**
     * @param $source
     *
     * @return array
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function getMetadata($source)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->getMetadata($source);
        } else {
            return $this->getSlaveConnection()->getMetadata($source);
        }
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function truncate($source)
    {
        return $this->getMasterConnection()->truncate($source);
    }

    /**
     * @param string $source
     *
     * @return static
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function drop($source)
    {
        return $this->getMasterConnection()->drop($source);
    }

    /**
     * @param null $schema
     *
     * @return array
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function getTables($schema = null)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->getTables($schema);
        } else {
            return $this->getSlaveConnection()->getTables($schema);
        }
    }

    /**
     * @param string $source
     *
     * @return bool
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function tableExists($source)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->tableExists($source);
        } else {
            return $this->getSlaveConnection()->tableExists($source);
        }
    }

    /**
     * @param array $params
     *
     * @return string
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function buildSql($params)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->buildSql($params);
        } else {
            return $this->getSlaveConnection()->buildSql($params);
        }
    }

    /**
     * @param string $sql
     *
     * @return string
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     * @throws \ManaPHP\Db\Exception
     */
    public function replaceQuoteCharacters($sql)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->replaceQuoteCharacters($sql);
        } else {
            return $this->getSlaveConnection()->replaceQuoteCharacters($sql);
        }
    }

    /**
     * @return void
     */
    public function close()
    {
        if ($this->_masterConnection !== null) {
            $this->_masterConnection->close();
        }

        if ($this->_slaveConnection !== null) {
            $this->_slaveConnection->close();
        }
    }
}