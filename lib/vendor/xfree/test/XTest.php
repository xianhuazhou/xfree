<?php
namespace xfree\test;
require __DIR__ . '/TestHelper/TestHelper.php';
use xfree\X;
use xfree\Controller;

class TestController extends Controller {
    public function indexAction() {
        echo 'test test indexAction';
    }
}

class XTest extends TestCase {
    public function setUp() {
    }

    public function tearDown() {
        TestHelper::initialize();
    }

    public function testConstants() {
        $this->assertEquals('get', X::METHOD_GET);
        $this->assertEquals('post', X::METHOD_POST);
        $this->assertEquals('put', X::METHOD_PUT);
        $this->assertEquals('delete', X::METHOD_DELETE);
        $this->assertEquals('', X::METHOD_ANY);
        $this->assertEquals('Action', X::ACTION_SUFFIX);
        $this->assertEquals('#', X::ROUTE_SEP);
    }

    public function testSet() {
        X::set('a', 'A');
        $this->assertEquals('A', X::get('a'));

        X::set('b', 12);
        $this->assertEquals(12, X::get('b'));
    }

    public function testGet() {
        X::set('numbers', array(1, 2, 3));
        $this->assertEquals(array(1, 2, 3), X::get('numbers'));
    }

    public function testVars() {
        $vars = X::vars();
        $this->assertTrue(true, is_array($vars));
        $this->assertEquals('test', $vars['x.env']);
    }

    public function testRemove() {
        X::set('blabla', 'blAblA');
        $this->assertEquals('blAblA', X::get('blabla'));

        X::remove('blabla');
        $this->assertNull(X::get('blabla'));
    }

    public function testAddRoute() {
        $this->assertEquals(array(), X::get('x.routes'));
        X::addRoute('/me', 'Me#index', X::METHOD_GET, 'me');

        $routes = X::get('x.routes');
        $this->assertEquals(1, count($routes));
        $this->assertEquals(array(
            'path' => '/me',
            'action' => 'Me#index',
            'method' => 'get',
            'name' => 'me'
        ), $routes[0]);
    }

    public function testValidator() {
        $this->assertTrue(X::validator() instanceof \xfree\Validator);
    }

    public function testFindRoute() {
        X::addRoute('/me', 'Me#index', X::METHOD_GET, 'me');
        X::addRoute('/users/:number', 'Users#index', X::METHOD_GET, 'users');
        X::addRoute('/users/add', 'Users#add', X::METHOD_POST, 'add_user');

        X::set('x.request.method', X::METHOD_GET);
        $routes = TestHelper::testFindRoute('/users/1', X::METHOD_GET);
        $this->assertEquals('/users/:number', $routes['path']);

        X::set('x.request.method', X::METHOD_POST);
        $routes = TestHelper::testFindRoute('/users/add', X::METHOD_POST);
        $this->assertEquals('/users/add', $routes['path']);
    }

    public function testFindRouteByName() {
        X::addRoute('/me', 'Me#index', X::METHOD_GET, 'me');

        $route = X::findRouteByName('me');
        $this->assertEquals('/me', $route['path']);

        $route = X::findRouteByName('Invalid...');
        $this->assertNull($route);
    }

    public function testRenderRoute() {
        X::set('__layout__', null);

        X::addRoute('/hi', function(){echo 'blabla';});
        $routes = X::get('x.routes');
        ob_start();
        TestHelper::testRenderRoute($routes[0]);
        $data = ob_get_clean();
        $this->assertEquals('blabla', $data);

        X::addRoute('/test', array(
            '200 OK',
            array('Content-Type: text/html'),
            '<!DOCTYPE html><head></head><body></body></html>'
        ));
        $routes = X::get('x.routes');
        ob_start();
        error_reporting(0);
        TestHelper::testRenderRoute($routes[1]);
        $data = ob_get_clean();
        $this->assertEquals('<!DOCTYPE html><head></head><body></body></html>', $data);

        X::addRoute('/my', 'xfree\test\TestController#index');
        $routes = X::get('x.routes');
        ob_start();
        TestHelper::testRenderRoute($routes[2]);
        $data = ob_get_clean();
        $this->assertEquals('test test indexAction', $data);
    }
}
