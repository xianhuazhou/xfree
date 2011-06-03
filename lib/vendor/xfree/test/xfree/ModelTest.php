<?php
namespace xfree\test;
require __DIR__ . '/../TestHelper.php';
use xfree\Model;

// PDO(sqlite)
class User extends Model {
    protected $FIELDS = array('id', 'name', 'pass', 'age');
    protected $PRIMARY_KEY = 'id';
}

// mongodb
class Book extends Model {
    protected $FIELDS = array('_id', 'author', 'title');
    protected $PRIMARY_KEY = '_id';
    protected $DATABASE_REFERENCE = 'mongo';
    protected $TABLE = 'books';
}

class ModelTest extends TestCase {
    public function setUp() { 
        $this->deleteSqliteDB();
        $this->deleteMongoDB();
        $user = new User();
        $user->getConnection()->exec('CREATE TABLE user(id INTEGER PRIMARY KEY ASC, name TEXT, pass TEXT, age INTEGER)');
    }

    public function tearDown() {
        $this->deleteMongoDB();
        $this->deleteSqliteDB();
    }

    public function testSetFields() {

        // PDO
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

        // MongoDB
        $book = new Book();
        $book->author = 'Author Name';
        $book->title = 'Book Title';
        $book->desc = 'Blabla...';
        $fields = $book->getFields();
        $this->assertTrue($fields['author'] == 'Author Name');
        $this->assertTrue($fields['title'] == 'Book Title');
        $this->assertFalse(isset($fields['desc']));
    }

    public function testCreate() {
        // PDO
        $user = new User();
        $user->name = 'User Name';
        $user->pass = 'userPass';
        $user->age = 20;
        $user->create();

        $items = $user->getConnection()->query("SELECT * FROM user")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, count($items));
        $this->assertEquals($items[0]['name'], 'User Name');

        // MongoDB
        $book = new Book();
        $book->author = 'Author Name';
        $book->title = 'Book Title';
        $book->desc = 'Blabla...';
        $book->create();
        $items = iterator_to_array($book->getConnection()->books->find(), false);
        $this->assertEquals(1, count($items));
        $this->assertEquals($items[0]['author'], 'Author Name');
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
