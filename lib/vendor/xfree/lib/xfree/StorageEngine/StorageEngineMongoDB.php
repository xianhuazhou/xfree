<?php
namespace xfree\StorageEngine;

/**
 * StorageEngineMongo
 */
class StorageEngineMongoDB {
    // mongoDB class
    private $mongoDB = null;

    // some properties to build query

    private $find;

    /**
     * construct
     *
     * @param MongoDB $connection
     */
    public function __construct($connection) {
        $this->mongoDB = $connection;
    }

    /**
     * create an new item
     *
     * @param string $table
     * @param array $fields
     *
     * @return string last insert id
     */
    public function create($table, Array $fields) {
        $this->mongoDB->$table->insert($fields, array('safe' => true));
        return $fields['_id'];
    }

    /**
     * update one or more items 
     *
     * @param string $table
     * @param array $fields
     * @param array $conditions
     *
     * @return bool
     */
    public function update($table, $fields, Array $conditions) {
        // default update action is "$set",
        // more: http://www.mongodb.org/display/DOCS/Updating
        $key = array_keys($fields);
        if (substr($key[0], 0, 1) != '$') {
            $fields = array('$set' => $fields);
        }

        return $this->mongoDB->$table->update($conditions, $fields);
    }

    /**
     * delete one or more items 
     *
     * @param string $table
     * @param array $fields
     * @param array $conditions
     *
     * @return bool
     */
    public function delete($table, $conditions) {
        return $this->mongoDB->$table->remove($conditions);
    }

    /**
     * get MongoDB connection
     *
     * @return MongoDB
     */
    public function getConnection() {
        return $this->mongoDB;
    }

    /**
     * find items
     *
     * @param string $table
     * @param array $conditions
     * @param array $fields
     *
     * @return array
     */
    public function find($table, $conditions = array(), $fields = array()) {
        $this->find = array(
            'table' => $table,
            'conditions' => $conditions,
            'fields' => $fields
        );
    }
}
