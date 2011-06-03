<?php
namespace xfree\test;
require __DIR__ . '/../TestHelper.php';
use xfree\Model;

class User extends Model {
    protected $FIELDS = array('id', 'name', 'pass', 'age');
    protected $PRIMARY_KEY = 'id';
}

class ModelTest extends TestCase {
    public function setUp() { 
        $this->deleteSqliteDB();
        $user = new User();
        $user->getConnection()->exec('CREATE TABLE user(id INTEGER PRIMARY KEY ASC, name TEXT, pass TEXT, age INTEGER)');
    }

    public function tearDown() {
        $this->deleteSqliteDB();
    }

    public function testSetFields() {
        $user = new User();
        $user->name = 'User Name';
        $user->pass = 'userPass';
        $user->about = 'About ...';
        $fields = $user->getFields();
        $this->assertTrue($fields['name'] == 'User Name');
        $this->assertTrue($fields['pass'] == 'userPass');
        $this->assertFalse(isset($fields['about']));
        $this->assertFalse(isset($fields['age']));

        $user = new User(array(
            'name' => 'User Name',
            'pass' => 'userPass',
            'about' => 'About ...',
        ));
        $fields = $user->getFields();
        $this->assertTrue($fields['name'] == 'User Name');
        $this->assertTrue($fields['pass'] == 'userPass');
        $this->assertFalse(isset($fields['about']));
        $this->assertFalse(isset($fields['age']));
    }

    public function testCreate() {
        $user = new User();
        $user->name = 'User Name';
        $user->pass = 'userPass';
        $user->age = 20;
        $user->create();

        $items = $user->getConnection()->query("SELECT * FROM user")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, count($items));
        $this->assertEquals($items[0]['name'], 'User Name');
    }

    public function testUpdate() {
        $user = new User(array(
            'name' => 'User Name',
            'pass' => 'userPass',
            'about' => 'About ...',
        ));
        $user->create();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('User Name', $user->name);
    }

    public function testDelete() {
        $user = new User(array(
            'name' => 'User Name',
            'pass' => 'userPass',
            'about' => 'About ...',
        ));
        $user->create();
        $user->delete();
        $items = $user->getConnection()->query("SELECT * FROM user")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, count($items));
    }
} 
