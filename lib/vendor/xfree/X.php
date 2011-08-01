<?php
/**
 * Core class: X
 */
namespace xfree;

class X {
    const ROUTE_SEP = "#";

    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const METHOD_PUT = 'put';
    const METHOD_DELETE = 'delete';
    const METHOD_ANY = '';

    const ACTION_SUFFIX = 'Action';

    // global vars
    private static $vars = array();

    // class paths
    private static $classPaths = array();

    /**
     * set a var
     * 
     * @param string $k
     * @param mixed $v
     */
    public static function set($k, $v) {
        self::$vars[$k] = $v;
    }

    /**
     * get a var 
     * 
     * @param string $k
     *
     * @return mixed
     */
    public static function get($k) {
        return isset(self::$vars[$k]) ? self::$vars[$k] : null;
    }

    /**
     * remove a variable 
     *
     * @return void
     * @author zhou 2011-05-19
     **/
    public static function remove($k)
    {
        unset(self::$vars[$k]);
    }

    /**
     * add a new route
     *
     * @param string $path
     * @param string $action
     * @param string $method
     * @param string $name
     */
    public static function addRoute($path, $action, $method = self::METHOD_ANY, $name = null) {    
        $routes = self::get('x.routes');
        $routes[] = array(
            'path' => $path,
            'action' => $action,
            'method' => $method,
            'name' => $name
        );
        self::set('x.routes', $routes);
    }

    /**
     * find a new route 
     *
     * @return array 
     **/
    protected static function findRoute($path, $method = self::METHOD_ANY) {
        if ($method != self::METHOD_ANY && $method != self::get('x.request.method')) {
            throw new InvalidRequestException();
        }

        foreach (self::get('x.routes') as $route) {
            $routePath = $route['path'];
            $routePathRegex = preg_replace('#/:\w+#', '/(\w+)', $routePath);
            if (preg_match('#^' . $routePathRegex . '$#', $path, $paramValues)) {
                array_shift($paramValues);

                preg_match_all('#/:\w+#', $routePath, $paramNames);
                $paramNames = array_map(function($v){
                    return substr($v, 2);
                },$paramNames[0]);

                foreach($paramNames as $k => $v) {
                    self::set('param.' . $v, $paramValues[$k]);
                }

                Logger::log(Logger::INFO, sprintf("%s - Matched route \"%s\": %s %s => %s", 
                    date('H:i:s'), 
                    $route['name'], 
                    $route['method'], 
                    $route['path'], 
                    $route['action']
                ));

                return $route;
            }
        }

        throw new NoRouteFoundException();
    }

    /**
     * find route by name 
     *
     * @return array 
     **/
    public static function findRouteByName($name)
    {
        if (!$name) {
            return;
        }
        foreach (self::get('x.routes') as $route) {
            if ($route['name'] == $name) {
                return $route;
            }
        }
    }

    /**
     * render the route 
     *
     * @return mixed 
     **/
    protected static function renderRoute($route)
    {
        $action = $route['action'];
        if (is_object($action) && get_class($action) == 'Closure') {
            return $action();
        }

        if (is_string($action)) {
            list($klass, $method) = explode(self::ROUTE_SEP, $action);
            $method = $method ?: 'index';
            if (!method_exists($klass, $method . self::ACTION_SUFFIX)) {
                throw new NoActionFoundException("class: " . $klass . ", method: " . $method);
            }
            return self::renderAction($klass, $method);
        }

        if (is_array($action)) {
            list($status, $headers, $body) = $action;

            if (strpos(PHP_SAPI, 'fcgi') !== false) {
                header('HTTP/1.1 ' . $status);
            } else {
                header('Status: ' . $status);
            }

            foreach ($headers as $header) {
                header($header);
            }

            echo $body;
            return;
        }
    }

    /**
     * render action 
     *
     * @param string $klass  class name
     * @param string $method  method name
     *
     * @return void
     **/
    protected static function renderAction($klass, $method)
    {
        Logger::log(Logger::INFO, sprintf('%s - Render action: %s::%s', date('H:i:s'), $klass, $method));
        v('x.current.controller', substr($klass, 0, -10));
        v('x.current.action', $method);
        $method .= self::ACTION_SUFFIX;
        $kls = new $klass;
        $kls->$method();
    }

    /**
     * render
     **/
    protected static function render() {
        $route = self::findRoute(
            self::get('x.request.uri'), 
            self::get('x.request.method')
        );
        v('x.current.route', $route);
        self::renderRoute($route);
    }

    /**
     * run the application
     *
     * @param array $options
     */
    public static function run($options = array()) {
        $startTime = microtime(true);

        Logger::log(Logger::INFO, sprintf('%s - Processing: %s', date('H:i:s'), $_SERVER['PHP_SELF']));

        if ($options['debug']) {
            ini_set('display_errors', true);
            error_reporting(E_ALL);
            v('x.debug', true);
        }

        try {
            self::render(); 
        } catch (\Exception $e) {
            $errorController = self::get('x.exception.controller');
            self::set('x.exception.object', $e);

            switch(get_class($e)) {
            case 'NoRouteFoundException':
            case 'NoActionFoundException':
                self::renderAction(
                    $errorController, 
                    'render404' 
                );
                break;

            default:
                self::renderAction(
                    $errorController, 
                    'render500' 
                );
            }
        }

        Logger::log(Logger::INFO, sprintf("%s - Done, Time: %fs\n\n", date('H:i:s'), microtime(true) - $startTime));
    }

    /**
     * autoload callback 
     *
     * @param string $klass class name
     **/
    public static function autoload($klass) {
        $rootDir = self::get('root_dir');
        $klassPath = '/' . str_replace('\\', '/', $klass) . '.php'; 
        foreach (self::$classPaths as $dir) {
            if (file_exists($dir . $klassPath)) {
                require $dir . $klassPath;
                return;
            }
        }
    }

    /**
     * initialize variables 
     *
     * @return void
     **/
    protected static function initialize() {
        $rootDir = ROOT_DIR;
        $xfreeDir = __DIR__;
        $appDir = $rootDir . '/app';
        $vendorDir = $rootDir . '/lib/vendor';
        $configDir = $rootDir . '/config';

        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        $xENV = substr($scriptName, 0, strpos($scriptName, '.'));

        self::$vars = array(
            'x.debug' => false,
            'x.routes' => array(), 
            'x.exception.controller' => '\\xfree\\ErrorController',
            'x.env' => $xENV,

            'root_dir' => $rootDir,
            'app_dir' => $appDir,
            'config_dir' => $rootDir . '/config',
            'controller_dir' => $appDir . '/controller',
            'model_dir' => $appDir . '/model',
            'view_dir' => $appDir . '/view',
            'lib_dir' => $rootDir . '/lib',
            'config_dir' => $rootDir . '/config',
            'helper_dir' => $rootDir . '/helper',
            'vendor_dir' => $rootDir . '/vendor',
            'xfree_lib_dir' => $xfreeDir . '/lib',
            'log_dir' => $rootDir . '/log',
            'log_file' => $rootDir . '/log/' . $xENV . '.log',
        );

        self::$classPaths = array(
            self::get('lib_dir'),
            self::get('model_dir'),
            self::get('controller_dir'),
            self::get('xfree_lib_dir'),
        );

        // load xfree related files
        require $xfreeDir . '/lib/helper/RootHelper.php';
        require $xfreeDir . '/lib/xfree/exceptions.php';

        // autoload
        spl_autoload_register(__CLASS__ . '::autoload');

        // It's running in the web environment
        if (isset($_SERVER['HTTP_HOST'])) {
            self::$vars = array_merge(self::$vars, array(
                'x.request.uri' => $_SERVER['REQUEST_URI'],
                'x.request.method' => $_SERVER['REQUEST_METHOD'],
                'x.request.host' => $_SERVER['HTTP_HOST'],
                'x.request.is_ajax' => isset($_SERVER['X_REQUESTED_WITH']) && $_SERVER['X_REQUESTED_WITH'] == 'XMLHttpRequest',
                'x.request.time' => $_SERVER['REQUEST_TIME'],
            ));

            // load routes
            require $configDir . '/routes.php';

            // load environment
            require $configDir . '/environments/' . $scriptName;
        }
    }
}
