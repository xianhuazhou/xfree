<?php
namespace xfree\StorageEngine;

/**
 * StorageEnginePDO contains PDO related actions.
 */
class StorageEnginePDO {

    // pdo connection
    private $pdo = null;

    /**
     * construct
     */
    public function __construct($connection) {
        $this->pdo = $connection;
    }

    /**
     * create a new item
     *
     * @param string $table
     * @param array $fields 
     *
     * @return bool
     */
    public function create($table, Array $fields) {
        $sql = "INSERT INTO " . $table . "(" . 
            join(',', array_keys($fields)) . 
            ") VALUES(" . join(',', array_fill(0, count($fields), '?')) . ")";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($fields));
        $stmt->closeCursor();
        $this->detectException($stmt); 
        return true;
    }

    /**
     * update one or more existing items 
     *
     * @param string $table
     * @param array $fields 
     * @param string $conditions
     *
     * @return int  updated rows 
     */
    public function update($table, Array $fields, $conditions) {
        $sql = "UPDATE " . $table . " SET " . 
            join(',', array_map(function($field){
                return $field . '=?';
            }, array_keys($fields))) . 
                " WHERE " . $conditions;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($fields));
        $rowCount = $stmt->rowCount();
        $stmt->closeCursor();
        $this->detectException($stmt); 
        return $rowCount;
    }

    /**
     * delete one or more items 
     *
     * @param string $table
     * @param array $fields 
     * @param string $conditions
     *
     * @return int deleted items
     */
    public function delete($table, $conditions) {
        $num = $this->pdo->exec("DELETE FROM " . $table . " WHERE " . $conditions);
        $this->detectException($this->pdo); 
        return $num;
    }

    /**
     * get PDO connection
     *
     * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * detect exception
     *
     * @param mixed $obj
     */
    private function detectException($obj) {
        if ('00000' != $obj->errorCode()) {
            throw new StorageEngineException('Can not execute the SQL: ' . $sql . "\n " . 
                print_r($obj->errorInfo(), true)
            );
        }
    }
}
