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

    // observer class
    protected $_observer = null;

    // some hookobserver related constants
    const HOOK_BEFORE_CREATE = 1;
    const HOOK_BEFORE_UPDATE = 2;
    const HOOK_BEFORE_DELETE = 3;
    const HOOK_AFTER_CREATE = 4;
    const HOOK_AFTER_UPDATE = 5;
    const HOOK_AFTER_DELETE = 6;

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

        $observerClass = get_class($this) . 'Observer';
        if (class_exists($observerClass)) {
            $this->setObserver($observerClass);
        }
    }

    /**
     * set observer
     *
     * @param string $observer
     */
    public function setObserver($observer = null) {
        $this->_observer = new $observer($this);
    }

    /**
     * get observer
     *
     * @param Observer $observer
     */
    public function getObserver() {
        return $this->_observer;
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
        $this->hookObserver(self::HOOK_BEFORE_CREATE);
        $lastInsertId = $this->_storageEngine->create(
            $this->getTable(), 
            $this->_fields, 
            $this->PRIMARY_KEY
        );
        if ($this->PRIMARY_KEY) {
            $this->_fields[$this->PRIMARY_KEY] = $lastInsertId;
        }
        $this->hookObserver(self::HOOK_AFTER_CREATE);

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
        $this->hookObserver(self::HOOK_BEFORE_UPDATE);
        $affectedRows = $this->_storageEngine->update(
            $this->getTable(), 
            $this->_fields, 
            $this->convertConditions($conditions)
        );
        $this->hookObserver(self::HOOK_AFTER_UPDATE);

        return $affectedRows;
    }

    /**
     * delete one or more items
     *
     * @param mixed $conditions
     *
     * @return int deleted items
     */
    public function delete($conditions = null) {
        $this->hookObserver(self::HOOK_BEFORE_DELETE);
        $affectedRows = $this->_storageEngine->delete($this->getTable(), $this->convertConditions($conditions));
        $this->hookObserver(self::HOOK_AFTER_DELETE);

        return $affectedRows;
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
        return strtolower(preg_replace('/(\w)([A-Z])/', '$1_$2', $this->getCurrentClassName()));
    }

    /**
     * hook observer
     *
     * @param int $hookType
     */
    protected function hookObserver($hookType) {
        $observer = $this->_observer;
        if (!($observer instanceof Observer)) {
            return;
        }

        $method = null;
        switch ($hookType) {
        case self::HOOK_BEFORE_CREATE:
            $method = 'beforeCreate';
            break;

        case self::HOOK_BEFORE_UPDATE:
            $method = 'beforeUpdate';
            break;

        case self::HOOK_BEFORE_DELETE:
            $method = 'beforeDelete';
            break;

        case self::HOOK_AFTER_CREATE:
            $method = 'afterCreate';
            break;

        case self::HOOK_AFTER_UPDATE:
            $method = 'afterUpdate';
            break;

        case self::HOOK_AFTER_DELETE:
            $method = 'afterDelete';
            break;
        }

        if ($method && method_exists($observer, $method)) {
            $observer->$method($this);
        }
    }

    /**
     * get current class name
     *
     * @return string
     */
    public function getCurrentClassName() {
        $table = explode('\\', get_class($this));
        return array_pop($table);
    }
}

