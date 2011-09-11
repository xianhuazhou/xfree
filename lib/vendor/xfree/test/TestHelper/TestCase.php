<?php
namespace xfree\test;

class TestCase extends \PHPUnit_Framework_TestCase {
    protected function deleteSqliteDB() {
        $sqlitedb = v('x.test.storage_engine_sqlitedb');
        if (file_exists($sqlitedb)) {
            unlink($sqlitedb);
        }
    }

    protected function deleteMongoDB() {
        $book = new Book();
        $book->getConnection()->drop();
    }
}
