<?php
namespace xfree\StorageEngine;

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
        } else {
            $this->adapter = new StorageEngineMongoDB($connection);
        }
    }

    /**
     * get a database connection
     *
     * @return mixed  connection resource handler
     */
    protected function getConnection() {
        $dsnInfo = $this->dsnInfo;
        $options = isset($dsnInfo['option']) ? $dsnInfo['option'] : array();

        // mongodb
        if (false !== strpos($dsnInfo['dsn'], 'mongodb://')) {
            if (isset($dsnInfo['username']) && isset($dsnInfo['password'])) {
                $options = array_merge($options, array(
                    'username' => $dsnInfo['username'],
                    'password' => $dsnInfo['password'],
                ));
            }
            $mongo = new \Mongo($dsnInfo['dsn'], $options);
            $mongoDB = explode('/', $dsnInfo['dsn']);
            return $mongo->selectDB(array_pop($mongoDB));

        // pdo
        } else {
            return new \PDO(
                $dsnInfo['dsn'], 
                isset($dsnInfo['username']) ? $dsnInfo['username'] : '', 
                isset($dsnInfo['password']) ? $dsnInfo['password'] : '', 
                $options
            );
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
     * find items
     *
     * @param string $table
     * @param mixed $conditions
     * @param array $fields
     *
     * @return array
     */
    public function find($table, $conditions = null, $fields = array()) {
        return $this->adapter->find($table, $conditions, $fields);
    }
}
