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
     * @param mixed $primaryKey
     *
     * @return int last insert id 
     */
    public function create($table, Array $fields, $primaryKey = null) {
        $sql = "INSERT INTO " . $table . "(" . 
            join(',', array_keys($fields)) . 
            ") VALUES(" . join(',', array_fill(0, count($fields), '?')) . ")";
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt) {
            $this->detectException($this->pdo, $sql);
        }
        $stmt->execute(array_values($fields));
        $this->detectException($stmt, $sql); 
        return $this->pdo->lastInsertId($primaryKey);
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
                " WHERE " . $this->convertConditions($conditions);
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt) {
            $this->detectException($this->pdo, $sql);
        }
        $stmt->execute(array_values($fields));
        $rowCount = $stmt->rowCount();
        $this->detectException($stmt, $sql); 
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
        $sql = "DELETE FROM " . $table . " 
            WHERE " . $this->convertConditions($conditions);
        $num = $this->pdo->exec($sql);
        $this->detectException($this->pdo, $sql); 
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
     * convert conditions for the "WHERE" caluse.
     *
     * @param mixed $conditions
     *
     * @return string
     */
    private function convertConditions($conditions) {
        if (is_string($conditions)) {
            return $conditions;
        }

        if (is_array($conditions)) {
            $where = array();
            foreach ($conditions as $k => $v) {
                $where[] = $k . "=" . $this->pdo->quote($v);
            }

            return join(' AND ', $where);
        }
    }

    /**
     * detect exception
     *
     * @param mixed $obj
     * @param string $sql
     */
    private function detectException($obj, $sql) {
        if ('00000' != $obj->errorCode()) {
            Logger::error(sprintf(
                "StorageEngine - exception: %s, sql: %s", 
                $obj->errorInfo(), 
                $sql
            ));
            throw new \xfree\exceptions\StorageEngineException('Can not execute the SQL: ' . $sql . "\n " . 
                print_r($obj->errorInfo(), true)
            );
        }
    }
}
