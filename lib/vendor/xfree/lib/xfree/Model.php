<?php
namespace xfree;
use xfree\StorageEngine\StorageEngine;

/**
 * Model class
 */
class Model {

    // fields
    protected $_fields = array();

    // reflection class
    protected $_refClass = null;

    // storage engine
    protected $_storageEngine = null;

    /**
     * construct
     *
     * @param mixed $fields
     */
    public function __construct($fields = null) {
        if (is_array($fields)) {
            $this->_fields = $fields;
        }
        $this->_refClass = new \ReflectionClass($this);
        $this->_storageEngine = new StorageEngine();
    }

    /**
     * invoked whenever update the model's property
     *
     * @param string $k
     * @param string $v
     */
    public function __set($k, $v) {
        if (!$this->_refClass->hasProperty($k)) {
            $this->_fields[$k] = $v;
        }
    }

    /**
     * getStorageEngine
     *
     * return mixed
     */
    protected function getStorageEngine() {
        return $this->_storageEngine;
    }

    /**
     * get storage engine connection
     *
     * @return PDO or MongoDB
     */
    protected function getConnection() {
        return $this->_storageEngine->getAdapter()->getConnection();
    }

    /**
     * find from storage engine
     *
     * @param mixed $conditions conditions for the query
     * @param array $fields  fields of the results to return
     *
     * @return array
     */
    public function find($conditions = null, $fields = array()) {
        return $this->_storageEngine->find($this->getTable(), $conditions, $fields);
    }

    /**
     * create an new item
     *
     * @return bool
     */
    public function create() {
        return $this->_storageEngine->create($this->getTable(), $this->_fields);
    }

    /**
     * update one or more items 
     *
     * @param string $conditions
     *
     * @return int  updated items 
     */
    public function update($conditions) {
       return $this->_storageEngine->update($this->getTable(), $this->_fields, $conditions);
    }

    /**
     * delete one or more items
     *
     * @param string $conditions
     *
     * @return int deleted items
     */
    public function delete($conditions) {
        $this->_storageEngine->delete($this->getTable(), $conditions);
    }

    /**
     * get table name
     *
     * @return string
     */
    protected function getTable() {
        if (isset($this->table)) {
            return $this->table;
        }
        return strtolower(preg_replace('/(\w)([A-Z])/', '$1_$2', get_class($this)));
    }
}

