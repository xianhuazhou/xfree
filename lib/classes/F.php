<?php
class F {
  const METHOD_GET = 'get';
  const METHOD_POST = 'post';
  const METHOD_ANY = '';

  private static $vars = array();

  public static function set($k, $v) {
    $vars[$k] = $v;
  }

  public static function get($k) {
    return $vars[$k];
  }

  private static function parseUri() {
  }

  public static function addRoute($path, $method = self::METHOD_ANY, $name = null) {    
    $routes = self::get('f.routes');
    $routes[] = array(
      'path' => $path,
      'method' => $method,
      'name' => $name
    );
    self::set('f.routes', $routes);
  }

  public static function findRoute($path, $method = self::METHOD_ANY) {
    if ($method != self::METHOD_ANY && $method != self::get('f.request.method')) {
      throw new InvalidRequestException();
    }

    foreach (self::get('f.routes') as $route) {

    }
  }

  public static function run() {
    self::set('routes', array());

    self::set('f.request.uri', $_SERVER['SCRIPT_NAME']);
    self::set('f.request.method', $_SERVER['REQUEST_METHOD']);
  }
}

// exceptions
class F_Exception extends Exception {}
class InvalidRequestException extends F_Exception {}
