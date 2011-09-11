<?php
namespace xfree\test;
require __DIR__ . '/../TestHelper/TestHelper.php';
use xfree\X;

class RootHelperTest extends TestCase {
    public function setUp() {
    }

    public function tearDown() {
        TestHelper::initialize();
    }

    public function testV() {
        v('a', 'A');
        $this->assertEquals('A', v('a'));
    }

    public function testO() {
        v('a', 'A');
        ob_start();
        o('a');
        $data = ob_get_clean();
        $this->assertEquals('A', $data);
    }

    public function testRemoveV() {
        v('a', 'A');
        remove_v('a');
        $this->assertNull(v('a'));
    }

    public function testParam() {
        v('param.a', 'A');
        $this->assertEquals('A', param('a'));

        $_POST['b'] = 'B';
        $this->assertEquals('B', param('b'));
    }

    public function testGet() {
        v('x.request.method', 'get');
        get('/me', 'MyController#index');
        $route = TestHelper::testFindRoute('/me', 'get');
        $this->assertEquals('/me', $route['path']);
    }

    public function testPost() {
        v('x.request.method', 'post');
        post('/me', 'MyController#index');
        $route = TestHelper::testFindRoute('/me', 'post');
        $this->assertEquals('/me', $route['path']);
    }

    public function testPut() {
        v('x.request.method', 'put');
        put('/me', 'MyController#index');
        $route = TestHelper::testFindRoute('/me', 'put');
        $this->assertEquals('/me', $route['path']);
    }

    public function testDelete() {
        v('x.request.method', 'delete');
        delete('/me', 'MyController#index');
        $route = TestHelper::testFindRoute('/me', 'delete');
        $this->assertEquals('/me', $route['path']);
    }

    public function testRequest() {
        v('x.request.method', 'get');
        request('/me', 'MyController#index');
        $route = TestHelper::testFindRoute('/me', '');
        $this->assertEquals('/me', $route['path']);
    }

    public function testRequestMethod() {
        $this->assertEquals('', request_method());

        v('x.request.method', 'GET');
        $this->assertEquals('GET', request_method());

        v('x.request.method', 'POST');
        $this->assertEquals('POST', request_method());

        v('x.request.method', 'PUT');
        $this->assertEquals('PUT', request_method());

        v('x.request.method', 'DELETE');
        $this->assertEquals('DELETE', request_method());
    }

    public function testIsPostRequest() {
        v('x.request.method', 'POST');
        $this->assertTrue(is_post_request());
    }

    public function testIsGetRequest() {
        v('x.request.method', 'GET');
        $this->assertTrue(is_get_request());
    }

    public function testIsPutRequest() {
        v('x.request.method', 'PUT');
        $this->assertTrue(is_put_request());
    }

    public function testIsDeleteRequest() {
        v('x.request.method', 'DELETE');
        $this->assertTrue(is_delete_request());
    }

    public function testIsAjaxRequest() {
        v('x.request.is_ajax', true);
        $this->assertTrue(is_ajax_request());
    }

    public function testPathFor() {
        get('/user/:id', 'UserController#getUser', 'get_user');
        $this->assertEquals('/user/3', path_for('get_user', array('id' => 3)));

        post('/user/add', 'UserController#addUser', 'add_user');
        $this->assertEquals('/user/add', path_for('add_user'));
    }

    public function testAssetHost() {
        $this->assertEquals('', asset_host());

        v('x.asset.host', 'xfree.asset.host');
        $this->assertEquals('xfree.asset.host', asset_host());
    }

    public function testAssetPath() {
        $this->assertEquals('/images', asset_path('/images'));

        v('x.request.host', 'xfree.lab');
        $this->assertEquals('http://xfree.lab/images', asset_path('/images', true));
    }

    public function testImagePath() {
        $this->assertEquals('/images/a.jpg', image_path('a.jpg'));
    }
}
