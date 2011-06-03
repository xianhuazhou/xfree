<?php
namespace xfree;
use xfree\StorageEngine\StorageEngine;

/**
 * Model class
 */
class Model {
    // fields
    protected $_fields = array();

    // storage engine
    protected $_storageEngine = null;

    /**
     * construct
     *
     * @param mixed $fields
     */
    public function __construct($fields = null) {
        if (is_array($fields)) {
           $this->setFields($fields); 
        }
        $this->_storageEngine = new StorageEngine(
            isset($this->DATABASE_REFERENCE) ? 
                $this->DATABASE_REFERENCE : 
                StorageEngine::DEFAULT_DATABASE_REFERENCE
        );
    }

    /**
     * set fields
     *
     * @param array $fields
     */
    protected function setFields(Array $fields) {
        foreach ($fields as $k => $v) {
            $this->setField($k, $v);
        }
    }

    /**
     * set field
     *
     * @param string $k
     * @param mixed $v
     *
     * @return bool
     */
    protected function setField($k, $v) {
        if (in_array($k, $this->FIELDS)) {
            $this->_fields[$k] = $v;
            return true;
        }

        return false;
    }

    /**
     * invoked whenever update the model's property
     *
     * @param string $k
     * @param string $v
     */
    public function __set($k, $v) {
       return $this->setField($k, $v); 
    }

    /**
     * invoked whenever get the model's property
     *
     * @param string $k
     * @param string $v
     *
     * @return mixed
     */
    public function __get($k) {
        if (isset($this->_fields[$k])) {
            return $this->_fields[$k];
        }
        return null;
    }

    /**
     * get fields
     *
     * @return array
     */
    public function getFields() {
        return $this->_fields;
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
    public function getConnection() {
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
     * @return Model 
     */
    public function create() {
        $lastInsertId = $this->_storageEngine->create(
            $this->getTable(), 
            $this->_fields, 
            $this->PRIMARY_KEY
        );
        if ($this->PRIMARY_KEY) {
            $this->_fields[$this->PRIMARY_KEY] = $lastInsertId;
        }
        return $this;
    }

    /**
     * update one or more items 
     *
     * @param mixed $conditions  update with the primary key if it's null, otherwise by the conditions
     *
     * @return int  updated items 
     */
    public function update($conditions = null) {
        return $this->_storageEngine->update(
            $this->getTable(), 
            $this->_fields, 
            $this->convertConditions($conditions)
        );
    }

    /**
     * delete one or more items
     *
     * @param mixed $conditions
     *
     * @return int deleted items
     */
    public function delete($conditions = null) {
        $this->_storageEngine->delete($this->getTable(), $this->convertConditions($conditions));
    }

    /**
     * convert conditions
     *
     * @param mixed $conditions
     *
     * @return mixed
     */
    private function convertConditions($conditions) {
        return $conditions === null ? 
            array($this->PRIMARY_KEY => $this->_fields[$this->PRIMARY_KEY]) : 
            $conditions;
    }

    /**
     * get table name
     *
     * @return string
     */
    protected function getTable() {
        if (isset($this->TABLE)) {
            return $this->TABLE;
        }
        $table = explode('\\', get_class($this));
        return strtolower(preg_replace('/(\w)([A-Z])/', '$1_$2', array_pop($table)));
    }
}

