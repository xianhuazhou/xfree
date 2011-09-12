<?php
use xfree\X;
use xfree\Logger;

function v() {
    $args = func_get_args();
    if (func_num_args() == 1) {
        return X::get($args[0]);
    }

    return X::set($args[0], $args[1]);
}

function o($v) {
    echo v($v);
}

function remove_v($v) {
    X::remove($v);
}

// get field value
function param($field) {
    return isset($_POST[$field]) ? $_POST[$field] : v('param.' . $field);
}

function get($path, $action, $name = '') {
    X::addRoute($path, $action, X::METHOD_GET, $name);
}

function post($path, $action, $name = '') {
    X::addRoute($path, $action, X::METHOD_POST, $name);
}

function put($path, $action, $name = '') {
    X::addRoute($path, $action, X::METHOD_PUT, $name);
}

function delete($path, $action, $name = '') {
    X::addRoute($path, $action, X::METHOD_DELETE, $name);
}

function request($path, $action, $name = '') {
    X::addRoute($path, $action, X::METHOD_ANY, $name);
}

// ---- Routes related -----

function redirect_to($url) {
    X::reroute($url);
}

function request_method() {
    return v('x.request.method');
}

function is_post_request() {
    return request_method() == 'POST';
}

function is_get_request() {
    return request_method() == 'GET';
}

function is_put_request() {
    return request_method() == 'PUT';
}

function is_delete_request() {
    return request_method() == 'DELETE';
}

function is_ajax_request() {
    return v('x.request.is_ajax');
}

function path_for($name, $options = array()) {
    $route = X::findRouteByName($name);
    $path = $route['path'];
    if ($options) {
        $path = str_replace(
            array_map(function($it){return ':' . $it;}, array_keys($options)), 
            array_values($options), 
            $path
        );
    }
    return $path;
}

// assets related functions

function asset_host() {
    return v('x.asset.host') ?: '';
}

function asset_path($file, $absolute = false) {
    if ($filesMapping = v('x.asset.files_mapping')) {
        if (isset($filesMapping[$file])) {
            $file = $filesMapping[$file];
        }
    }
    $file = asset_host() . $file;
    if ($absolute && (false === strpos($file, 'http'))) {

        # TODO need check for SSL?
        $file = 'http://' . v('x.request.host') . $file; 
    }

    return $file;
}

function image_path($image, $absolute = false) {
    return asset_path(v('x.asset.image_directory') . '/' . $image, $absolute);
}

function js_path($js, $absolute = false) {
    return asset_path(v('x.asset.js_directory') . '/' . $js, $absolute);
}

function css_path($css, $absolute = false) {
    return asset_path(v('x.asset.css_directory') . '/' . $css, $absolute);
}

// --- render layout view related --
function render($template = null) {
    $controllerDir = str_replace(
        '\\', 
        '/', 
        controller_name()
    );

    if ($template == null) {
        $template = $controllerDir . '/' . action_name() . '.php';
    } else {
        if (false === strpos($template, '.php')) {
            $template .= '.php';
        }
        if (false === strpos($template, '/')) {
            $template = $controllerDir . '/' . $template;
        }
    }

    $viewDir = v('view_dir');

    if ('/' != substr($template, 0, 1)) {
        $template = $viewDir . '/' . $template;
    }

    ob_start();
    require $template;
    $viewResult = ob_get_clean();

    if (v('__layout__')) {
        v('__view_content__', $viewResult);
        require $viewDir . '/layout/' . v('__layout__') . '.php';
    } else {
        echo $viewResult;
    }

    Logger::info(sprintf(
        'Render template: %s', $viewDir . '/' . $template
    ));
}

function renderAsString($template = null) {
    ob_start();
    render($template);
    return ob_get_clean();
}

function current_route() {
   return v('x.current.route'); 
}

function controller_name() {
    return v('x.current.controller');
}

function action_name() {
    return v('x.current.action');
}

function layout($layout) {
    v('__layout__', $layout);
}

function yield($name = '__view_content__') {
    o($name);
}
// end --- render layout view related --

// --- slot --
function start_slot() {
    ob_start();
}

function end_slot_as($name) {
    $slotData = ob_get_clean();

    xfree\Logger::log(
        xfree\Logger::INFO, 
        sprintf('%s - Slot created: %s', date('H:i:s'), $name)
    );

    return v('__slot_data__' . $name, $slotData);
}

function yield_slot($name) {
    o('__slot_data__' . $name);
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
        require $f;
    }
}
