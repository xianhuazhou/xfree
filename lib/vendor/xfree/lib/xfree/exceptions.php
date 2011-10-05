<?php
namespace xfree\exceptions;
// exceptions
class X_Exception extends \Exception {}
class InvalidRequestException extends X_Exception {
    public function __construct() {
        parent::__construct('Invalid request');
    }
}
class NoRouteFoundException extends X_Exception {
    public function __construct() {
        parent::__construct(sprintf('No Route found: %s', v('x.request.uri')));
    }
}
class NoActionFoundException extends X_Exception {
    public function __construct() {
        parent::__construct('No Action found');
    }
}
class StorageEngineException extends X_Exception {
    public function __construct() {
        parent::__construct('StorageEngine error');
    }
}
class PDOConnectionException extends X_Exception {
    public function __construct() {
        parent::__construct('No available PDO Connection');
    }
}
