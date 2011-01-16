<?php
function v() {
  $args = func_get_args();
  if (func_num_args() == 1) {
    return F::get($args[0]);
  }

  return F::set($args[0], $args[1]);
}

function o($v) {
  echo v($v);
}

function v_clear($v) {
  F::clear($v);
}

// get field value
function param($field) {
  return @$_POST[$field] ?: (@$_GET[$field] ?: v('PARAMS["' . $field . '"]'));
}

function load_configuration_for($files) {
  array_map(function($file){
    $vars = include(v('path.config') . '/' . $file . '.php');
    array_walk($vars, function($item, $key){
      v($key, $item);
    });
  }, $files); 
}

// ---- Routes related -----
function route($verb, $path, $funcs, $name = '', $ttl = 0, $allow = true) {
  return RootRoute::route($verb, $path, $funcs, $name, $ttl, $allow);
}

function get($path, $funcs, $name = '', $ttl = 0, $allow = true) {
  route('GET', $path, $funcs, $name, $ttl, $allow);
}

function post($path, $funcs, $name = '', $ttl = 0, $allow = true) {
  route('POST', $path, $funcs, $name, $ttl, $allow);
}

function put($path, $funcs, $name = '', $ttl = 0, $allow = true) {
  route('PUT', $path, $funcs, $name, $ttl, $allow);
}

function delete($path, $funcs, $name = '', $ttl = 0, $allow = true) {
  route('DELETE', $path, $funcs, $name, $ttl, $allow);
}
// ---- Routes related -----

function redirect_to($url) {
  F::reroute($url);
}

function is_post() {
  return $_SERVER['REQUEST_METHOD'] == 'POST';
}

function is_get() {
  return $_SERVER['REQUEST_METHOD'] == 'GET';
}

function path_for($name, $options = array()) {
  $path = RootRoute::$mapping[$name];
  if ($options) {
    $path = str_replace(
      array_map(function($it){return '@' . $it;}, array_keys($options)), 
      array_values($options), 
      $path
    );
  }
  return $path;
}

// --- render layout view related --
function render($template = null) {
  v('__rendered__', true);

  if ($template == null) {
    $template = strtolower(controller_name()) . '/' . strtolower(action_name()) . '.php';
  }

  ob_start();
  require(v('path.app.view') . '/' . $template);
  $viewResult = ob_get_contents();
  ob_end_clean();

  if (v('__layout__')) {
    v('__view_content__', $viewResult);
    require v('path.app.view') . '/layout/' . v('__layout__') . '.php';
  } else {
    echo $viewResult;
  }
}

function current_route() {
  foreach(v('ROUTES') as $regex => $route) {
    if (preg_match(
      $regex,
      substr($_SERVER['REQUEST_URI'], strlen(v('BASE')))
    )) {
      if (isset($route[$_SERVER['REQUEST_METHOD']])) {
        $route = array_pop(array_values($route));
        return $route[0];
      }
    }
  }
}

function controller_name() {
  $route = current_route();
  return str_replace(RootRoute::CONTROLLER_CLASS_SUFFIX, '', get_class($route[0]));
}

function action_name() {
  $route = current_route();
  return $route[1];
}

function layout($layout) {
  v('__layout__', $layout);
}

function yield($name = '__view_content__') {
  return v($name);
}
// end --- render layout view related --

// --- slot --
function start_slot() {
  ob_start();
}

function end_slot_as($name) {
  $slotData = ob_get_contents();
  ob_end_clean();

  return v('__slot_data__' . $name, $slotData);
}

function use_slot($name) {
  return v('__slot_data__' . $name);
}
// end --- slot --

function h($v) {
  return htmlspecialchars($v);
}

function has_flash($k) {
  return isset($_SESSION[$k]);
}

function flash($k, $v = null) {
  if ($v === null) {
    $v = $_SESSION[$k];
    unset($_SESSION[$k]);
    return $v;
  }

  return $_SESSION[$k] = $v;
}

// -------------- ----------------
// load other helpers
foreach(glob(__DIR__ . '/*.php') as $f) {
  if ($f != __FILE__) {
    require_once $f;
  }
}
