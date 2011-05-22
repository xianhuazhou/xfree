<?php
define('ROOT_DIR', realpath('../'));
require ROOT_DIR . '/lib/vendor/xfree/X.php';

class Application extends xfree\X {
    public static function initialize() {
        parent::initialize();
        load_configuration_for(array());
        layout('default');
    }
}
Application::initialize();
