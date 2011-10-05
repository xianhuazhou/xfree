<?php
namespace xfree\StorageEngine;
use xfree\Logger;

/**
 * StorageEngine, supports PDO and MongoDB
 */
class StorageEngine {

    // default database reference name
    const DEFAULT_DATABASE_REFERENCE = 'default';

    // current dsnInfo
    private $dsnInfo = null;

    // all initialized connections
    private $connections = array();

    // adapter
    private $adapter = null;
    private $slaveAdapter = null;

    /**
     * construct, initialize a database connection
     *
     * @param string $databaseRef
     */
    public function __construct($databaseRef = self::DEFAULT_DATABASE_REFERENCE) {
        $dsns = v('x.storage_engine');
        $this->dsnInfo = $dsns[$databaseRef];

        // initialize database connections
        if (!isset($this->connections[$databaseRef])) {
            $connection = $this->getConnection();
            $this->connections[$databaseRef] = $connection;
        } else {
            $connection = $this->connections[$databaseRef];
        }

        if ($connection instanceof \PDO) {
            $this->adapter = new StorageEnginePDO($connection);
            if (!$this->slaveAdapter) {
                $this->adapter = $this->slaveAdapter();
            }
        } else {
            $this->adapter = new StorageEngineMongoDB($connection);
            $this->slaveAdapter = $this->adapter;
        }
    }

    /**
     * get a database connection
     *
     * @return mixed  connection resource handler
     */
    protected function getConnection() {
        $dsnInfo = $this->dsnInfo;
        // mongodb
        if (isset($dsnInfo['dsn']) && (false !== strpos($dsnInfo['dsn'], 'mongodb://'))) {
            $options = isset($dsnInfo['option']) ? $dsnInfo['option'] : array();
            if (isset($dsnInfo['username']) && isset($dsnInfo['password'])) {
                $options = array_merge($options, array(
                    'username' => $dsnInfo['username'],
                    'password' => $dsnInfo['password'],
                ));
            }
            $mongo = new \Mongo($dsnInfo['dsn'], $options);
            $mongoDB = explode('/', $dsnInfo['dsn']);
            Logger::info(sprintf('initialize MongoDB connection'));
            return $mongo->selectDB(array_pop($mongoDB));

        // pdo
        } else {
            Logger::info(sprintf('StoreEngine - initialize PDO connection'));

            $masterConnection = $this->getPDOConnection($dsnInfo['master']);

            if ($slaveConnection = $this->getPDOConnection($dsnInfo['slave'], false)) {
                $this->slaveAdapter = new StorageEnginePDO($slaveConnection);
            }

            return $masterConnection; 
        }
    }

    /**
     * get one  random dsninfo from multiple masters or slaves 
     *
     * @param array $dsnInfos
     * @param bool $throwException true means throw an exception if there is 
     *                             no available connection found, return null on false 
     * @return array
     */
    private function getPDOConnection(Array $dsnInfos, $throwException = true) {
        $allDSNInfos = array();

        foreach ($dsnInfos as $dsnInfo) {
            $weight = isset($dsnInfo['weight']) ? $dsnInfo['weight'] : 1;
            $allDSNInfos = array_pad($allDSNInfos, count($allDSNInfos) + $weight, $dsnInfo);
        }

        shuffle($allDSNInfos);

        foreach ($allDSNInfos as $dsnInfo) {
            try {
                $options = isset($dsnInfo['options']) ? $dsnInfo['options'] : array();
                return new \PDO(
                    $dsnInfo['dsn'], 
                    isset($dsnInfo['username']) ? $dsnInfo['username'] : '', 
                    isset($dsnInfo['password']) ? $dsnInfo['password'] : '', 
                    $options
                );
            } catch (\PDOException $e) {
                Logger::error(sprintf(
                    'Invalid PDO Connection: %s, DSN info: %s',
                    $e->getMessage(), $dsnInfo['dsn']
                ));
                continue;
            }
        }

        if ($throwException) {
            Logger::error(sprintf(
                'StoreEngine - No PDO Connection avaiable.: %s',
                print_r($dsnInfos, true)
            ));
            throw new \xfree\exceptions\PDOConnectionException();

        } else {
            return null;
        }
    }

    /**
     * create a new item
     *
     * @param string $table
     * @param array $fields
     *
     * @return mixed  last insert id 
     */
    public function create($table, Array $fields) {
        Logger::info(sprintf(
            'StoreEngine - create item: %s, table: %s', 
            "'" . json_encode($fields) . "'",
            $table
        ));
        return $this->adapter->create($table, $fields);
    }

    /**
     * update an item 
     *
     * @param string $table
     * @param array $fields
     * @param mixed $conditions
     *
     * @return mixed number of updated items in PDO, true or false in Mongo
     */
    public function update($table, Array $fields, $conditions) {
        Logger::info(sprintf(
            'StoreEngine - update item: %s, conditions: (%s), table: %s', 
            "'" . json_encode($fields) . "'", 
            "'" . json_encode($conditions) . "'",
            $table
        ));
        return $this->adapter->update($table, $fields, $conditions);
    }

    /**
     * delete an item 
     *
     * @param string $table
     * @param mixed $conditions
     *
     * @return mixed number of deleted items in PDO, true or false in Mongo
     */
    public function delete($table, $conditions) {
        Logger::info(sprintf(
            'StoreEngine - delete item, conditions: (%s), table: %s', 
            "'" . json_encode($conditions) . "'",
            $table
        ));
        return $this->adapter->delete($table, $conditions);
    }

    /**
     * get adapter
     *
     * @return StorageEnginePDO or StorageEngineMongoDB
     */
    public function getAdapter() {
        return $this->adapter;
    }

    /**
     * get slave adapter
     *
     * @return StorageEnginePDO or StorageEngineMongoDB
     */
    public function getSlaveAdapter() {
        return $this->slaveAdapter;
    }

    /**
     * find items
     *
     * @param string $table
     * @param mixed $conditions
     * @param array $fields
     *
     * @return array
     */
    public function find($table, $conditions = null, $fields = array()) {
        Logger::info(sprintf(
            'StoreEngine - find item, fields: %s, conditions: (%s), table: %s', 
             "'" . json_encode($fields) . "'",
             "'" . json_encode($conditions) . "'",
            $table
        ));
        return $this->slaveAdapter->find($table, $conditions, $fields);
    }
}
