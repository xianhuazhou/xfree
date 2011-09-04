<?php
// development environment settings 

// storage engine
v('x.storage_engine', array(
    /*
    'default' => array(
        array(
            'master' => array(
                array(
                    'dsn' => 'mysql:host=localhost;dbname=xfree',
                    'username' => 'root',
                    'password' => ''
                )
            )
        )
    )
     */
    'default' => array(
        'dsn' => 'mongodb://localhost:27017/user'
    )
));
v('x.log_level', xfree\Logger::DEBUG);
