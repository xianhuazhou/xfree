<?php
get('/', 'DefaultController#index', 'homepage');
get('/users', 'UserController#index', 'users');
get('/hello/:name', function(){
    echo 'Hello: ' . param('name');
});
get('/response', array('200 OK', array('Content-Type: text/xml'), '<?xml version="1.0" encoding="utf-8"?><root><node>a</node><node>b></node></root>'));
