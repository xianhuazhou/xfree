<?php
require __DIR__ . '/../lib/vendor/xfree/X.php';

class Application extends xfree\X {
    public static function initialize() {
        parent::initialize();
        load_configuration_for(array('settings', 'db'));
        layout('default');
    }
}
Application::initialize();
