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
    protected $FIELDS = array('id', 'title', 'desc');
    protected $PRIMARY_KEY = 'id';
}
class PageObserver extends Observer {
    public function beforeCreate($page) {
        $page->title = $page->title . ' Observer';
    }
}

// Validator
class Post extends Model {
    protected $FIELDS = array(
        'id', 
        'title' => array(
            'validations' => array(
                'string' => array(
                    'options' => array(
                        'min_length' => 5,
                        'max_length' => 20
                    ),
                    'errors' => array(
                        'min_length' => 'Title is too short',
                        'max_length' => 'Title is too long'
                    ),
                ),
            ),
            'if' => 'not_blank',
        ),
        'content' => array(
            'validations' => array(
                'not_blank' => array(
                    'error_message' => 'Content can not be blank'
                ),
            ),
        ),
        'publish' => array(
            'validations' => array(
                'integer' => array(
                    'options' => array(
                        'in_array' => array(1, 0)
                    ),
                    'errors' => array(
                        'in_array' => 'publish should be 1 or 0'
                    ),
                    'error_message' => 'publish is invalid'
                )
            ),
        ),
    );
    protected $PRIMARY_KEY = 'id';
}

class ModelTest extends TestCase {
    public function setUp() { 
        $this->deleteSqliteDB();
        $this->deleteMongoDB();
        $user = new User();
        $user->getConnection()->exec('CREATE TABLE user(id INTEGER PRIMARY KEY ASC, name TEXT, pass TEXT, age INTEGER)');

        $page = new Page();
        $page->getConnection()->exec('CREATE TABLE page(id INTEGER PRIMARY KEY ASC, title TEXT, desc TEXT)');

        $post = new Post();
        $post->getConnection()->exec('CREATE TABLE post(id INTEGER PRIMARY KEY ASC, title TEXT, content TEXT, publish INTEGER');
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
        $fields = $user->getFields();
        $this->assertTrue($fields['name'] == 'User Name');
        $this->assertTrue($fields['pass'] == 'userPass');
        $this->assertFalse(isset($fields['age']));

        $user = new User(array(
            'name' => 'User Name',
            'pass' => 'userPass',
        ));
        $fields = $user->getFields();
        $this->assertTrue($fields['name'] == 'User Name');
        $this->assertTrue($fields['pass'] == 'userPass');
        $this->assertFalse(isset($fields['age']));

        // MongoDB
        $book = new Book();
        $book->author = 'Author Name';
        $book->title = 'Book Title';
        $fields = $book->getFields();
        $this->assertTrue($fields['author'] == 'Author Name');
        $this->assertTrue($fields['title'] == 'Book Title');
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
        $book->create();
        $items = iterator_to_array($book->getConnection()->books->find(), false);
        $this->assertEquals(1, count($items));
        $this->assertEquals('Author Name', $items[0]['author']); 
    }

    public function testValidate() {
        $post = new Post();
        $post->title = 'new post';
        $post->content = 'content ...';
        $post->publish = 1;
        $this->assertTrue($post->validate());

        $post->publish = null;
        $this->assertFalse($post->validate());
        $this->assertTrue($post->hasError('publish'));
        $this->assertEquals('publish is invalid', $post->getError('publish'));

        $post->publish = 2;
        $this->assertFalse($post->validate());
        $this->assertTrue($post->hasError('publish'));
        $this->assertEquals('publish should be 1 or 0', $post->getError('publish'));

        $post->publish = 1;
        $post->content = '';
        $this->assertFalse($post->validate());
        $this->assertTrue($post->hasError('content'));
        $this->assertEquals('Content can not be blank', $post->getError('content'));

    }

    public function testUpdate() {
        // PDO
        $user = new User(array(
            'name' => 'User Name',
            'pass' => 'userPass',
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
