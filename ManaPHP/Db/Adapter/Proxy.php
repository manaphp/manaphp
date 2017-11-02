<?php
namespace ManaPHP\Db\Adapter;

use ManaPHP\Component;
use ManaPHP\Db\Adapter\Proxy\Exception as ProxyException;
use ManaPHP\DbInterface;

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
     * @var \ManaPHP\DbInterface
     */
    protected $_masterConnection;

    /**
     * @var \ManaPHP\DbInterface
     */
    protected $_slaveConnection;

    /**
     * @var \ManaPHP\DbInterface
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
     * @return \ManaPHP\DbInterface
     * @throws \ManaPHP\Db\Exception
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     */
    public function getMasterConnection()
    {
        if ($this->_masterConnection === null) {
            $master = $this->_selectDbConfig($this->_masters);

            if ($master === false) {
                throw new ProxyException('there is no available master server.');
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
     * @return \ManaPHP\DbInterface
     * @throws \ManaPHP\Db\Exception
     * @throws \ManaPHP\Db\Adapter\Proxy\Exception
     */
    public function getSlaveConnection()
    {
        if ($this->_slaveConnection === null) {
            $slave = $this->_selectDbConfig($this->_slaves);
            if ($slave === false) {
                throw new ProxyException('there is no available slave server.');
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
     * @return \ManaPHP\DbInterface
     */
    public function getCurrentConnection()
    {
        return $this->_currentConnection;
    }

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
        return $this->_dependencyInjector->get('ManaPHP\Db\Query', [$this]);
    }

    public function execute($sql, $bind = [])
    {
        return $this->getMasterConnection()->execute($sql, $bind);
    }

    public function affectedRows()
    {
        return $this->_currentConnection->affectedRows();
    }

    public function fetchOne($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC)
    {
        if ($this->isUnderTransaction()) {
            return $this->getMasterConnection()->fetchOne($sql, $bind, $fetchMode);
        } else {
            return $this->getSlaveConnection()->fetchOne($sql, $bind, $fetchMode);
        }
    }

    public function fetchAll($sql, $bind = [], $fetchMode = \PDO::FETCH_ASSOC, $indexBy = null)
    {
        if ($this->isUnderTransaction()) {
            return $this->getMasterConnection()->fetchAll($sql, $bind, $fetchMode, $indexBy);
        } else {
            return $this->getSlaveConnection()->fetchAll($sql, $bind, $fetchMode, $indexBy);
        }
    }

    public function insert($table, $fieldValues)
    {
        $this->getMasterConnection()->insert($table, $fieldValues);
    }

    public function update($table, $fieldValues, $conditions, $bind = [])
    {
        return $this->getMasterConnection()->update($table, $fieldValues, $conditions, $bind);
    }

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

    public function begin()
    {
        $this->getMasterConnection()->begin();

        $this->_transactionLevel++;
    }

    public function isUnderTransaction()
    {
        return $this->_transactionLevel > 0;
    }

    public function rollback()
    {
        $this->_currentConnection->rollback();

        $this->_transactionLevel--;
    }

    public function commit()
    {
        $this->_currentConnection->commit();

        $this->_transactionLevel--;
    }

    public function lastInsertId()
    {
        return $this->_currentConnection->lastInsertId();
    }

    public function getMetadata($source)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->getMetadata($source);
        } else {
            return $this->getSlaveConnection()->getMetadata($source);
        }
    }

    public function truncateTable($source)
    {
        return $this->getMasterConnection()->truncateTable($source);
    }

    public function dropTable($source)
    {
        return $this->getMasterConnection()->dropTable($source);
    }

    public function getTables($schema = null)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->getTables($schema);
        } else {
            return $this->getSlaveConnection()->getTables($schema);
        }
    }

    public function tableExists($source)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->tableExists($source);
        } else {
            return $this->getSlaveConnection()->tableExists($source);
        }
    }

    public function buildSql($params)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->buildSql($params);
        } else {
            return $this->getSlaveConnection()->buildSql($params);
        }
    }

    public function replaceQuoteCharacters($sql)
    {
        if ($this->_masterConnection !== null) {
            return $this->_masterConnection->replaceQuoteCharacters($sql);
        } else {
            return $this->getSlaveConnection()->replaceQuoteCharacters($sql);
        }
    }

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