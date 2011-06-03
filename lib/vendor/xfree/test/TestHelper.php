<?php
namespace xfree\test;

define('ROOT_DIR', realpath(__DIR__ . '/..'));
require ROOT_DIR . '/X.php';
require __DIR__ . '/TestCase.php';

use xfree\X;

class TestHelper extends X {
    public static function initialize() {
        parent::initialize();
        load_configuration_for(array());
        layout('default');
        self::configStorageEngine();
    }

    private static function configStorageEngine() {
        v('x.test.dir', realpath(__DIR__));
        v('x.test.storage_engine_data_dir', realpath(__DIR__ . '/xfree/StorageEngine/data'));
        v('x.test.storage_engine_sqlitedb', v('x.test.storage_engine_data_dir') . '/sqlite.db');
        v('x.storage_engine', array(
            'default' => array(
                'dsn' => 'sqlite:' . v('x.test.storage_engine_sqlitedb')
            )
        ));
    }
}

TestHelper::initialize();
