<?php
namespace xfree\test;
require __DIR__ . '/../TestHelper/TestHelper.php';
use xfree\Model;
use xfree\Observer;

// PDO(sqlite)
class User extends Model {
    protected $FIELDS = array('id', 'name', 'pass', 'age');
    protected $PRIMARY_KEY = 'id';
}

// mongodb (required MongoDB running)
class Book extends Model {
    protected $FIELDS = array('_id', 'author', 'title');
    protected $PRIMARY_KEY = '_id';
    protected $DATABASE_REFERENCE = 'mongo';
    protected $TABLE = 'books';
}

// observer
class Page extends Model {
    protected $FIELDS = array('title', 'desc');
    protected $PRIMARY_KEY = 'id';
}
class PageObserver extends Observer {
    public function beforeCreate($page) {
        $page->title = $page->title . ' Observer';
    }
}

class ModelTest extends TestCase {
    public function setUp() { 
        $this->deleteSqliteDB();
        $this->deleteMongoDB();
        $user = new User();
        $user->getConnection()->exec('CREATE TABLE user(id INTEGER PRIMARY KEY ASC, name TEXT, pass TEXT, age INTEGER)');

        $page = new Page();
        $page->getConnection()->exec('CREATE TABLE page(id INTEGER PRIMARY KEY ASC, title TEXT, desc TEXT)');
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
        $this->assertEquals('User Name', $items[0]['name']);

        // MongoDB
        $book = new Book();
        $book->author = 'Author Name';
        $book->title = 'Book Title';
        $book->desc = 'Blabla...';
        $book->create();
        $items = iterator_to_array($book->getConnection()->books->find(), false);
        $this->assertEquals(1, count($items));
        $this->assertEquals('Author Name', $items[0]['author']);
    }

    public function testUpdate() {
        // PDO
        $user = new User(array(
            'name' => 'User Name',
            'pass' => 'userPass',
            'about' => 'About ...',
        ));
        $user->create();
        $this->assertEquals(1, $user->id);
        $this->assertEquals('User Name', $user->name);

        $user->name = 'New User Name';
        $user->update();
        $this->assertEquals('New User Name', $user->name);
        $items = $user->getConnection()->query("SELECT * FROM user")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals(1, count($items));
        $this->assertEquals('New User Name', $items[0]['name']);

        // MongoDB
        $book = new Book(array(
            'author' => 'Author Name',
            'title' => 'Book Title',
        ));
        $book->create();
        $this->assertTrue($book->_id instanceof \MongoId);
        $this->assertEquals('Book Title', $book->title);

        $book->title = 'New Book Title';
        $book->update();
        $this->assertEquals('New Book Title', $book->title);
        $items = iterator_to_array($book->getConnection()->books->find(), false);
        $this->assertEquals(1, count($items));
        $this->assertEquals('New Book Title', $items[0]['title']);
    }

    public function testDelete() {
        // PDO
        $user = new User(array(
            'name' => 'User Name',
            'pass' => 'userPass',
            'about' => 'About ...',
        ));
        $user->create();
        $user->delete();
        $items = $user->getConnection()->query("SELECT * FROM user")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals(0, count($items));

        // MongoDB
        $book = new Book(array(
            'author' => 'Author Name',
            'title' => 'Book Title',
        ));
        $book->create();
        $book->delete();
        $items = iterator_to_array($book->getConnection()->books->find(), false);
        $this->assertEquals(0, count($items));
    }

    public function testHookObserver() {
        $page = new Page(array(
            'title' => 'Page Title',
            'desc' => 'blabla',
        ));
        $page->create();
        $this->assertEquals('Page Title Observer', $page->title);
        $this->assertEquals('blabla', $page->desc);
        $items = $page->getConnection()->query("SELECT * FROM page")->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertEquals('Page Title Observer', $items[0]['title']);
        $this->assertEquals('blabla', $items[0]['desc']);
    }
} 
