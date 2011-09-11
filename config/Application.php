<?php
define('ROOT_DIR', realpath(__DIR__ . '/..'));
require ROOT_DIR . '/lib/vendor/xfree/X.php';

class Application extends xfree\X {
    public static function initialize() {
        parent::initialize();
        layout('default');
    }
}

Application::initialize();
