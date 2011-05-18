<?php
class RootRoute {
  const ROUTE_SEP = '#';
  const CONTROLLER_CLASS_SUFFIX = 'Controller';
  public static $mapping = array();

  public static function route($verb, $path, $funcs, $name, $ttl, $allow)
  {
    if ($name == '') {
      $name = $path;
    }

    self::$mapping[$name] = $path;

    if (is_string($funcs) && strpos($funcs, self::ROUTE_SEP)) {
      list($klass, $method) = explode(self::ROUTE_SEP, $funcs);
      $originKlass = $klass;
      $klass = str_replace('/', '\\', $klass) . self::CONTROLLER_CLASS_SUFFIX; 
      $method = $method ?: 'index';

      if (!method_exists($klass, $method)) {
        return;
        //throw new Exception('No method ' . $klass . '#' . $method . ' found.');
      }

      // loade helper
      foreach(glob(v('path.app.helper') . '/' . self::controllerNameOfKlass($originKlass) . '/*.php') as $helper) {
        require_once $helper;
      }
      
      $funcs = array(new $klass, $method);
    }
    
    F::route($verb . ' ' . $path, $funcs, $ttl, $allow);
  }

  private static function controllerNameOfKlass($klass) {
    return str_replace('\\', '/', strtolower($klass));
  }
}
