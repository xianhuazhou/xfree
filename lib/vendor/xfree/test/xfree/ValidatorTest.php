<?php
namespace xfree\test;
require __DIR__ . '/../TestHelper/TestHelper.php';
use xfree\Validator;

class ValidatorTest extends TestCase {
    public function setUp() {
        $this->validator = new Validator();
    }

    public function tearDown() {
        $this->validator = null;
    }

    public function testEmail() {
        $this->assertFalse($this->validator->email('user@domain'));
        $this->assertTrue($this->validator->email('user@domain.com'));
    }

    public function testIp() {
        $this->assertFalse($this->validator->ip('1.1'));
        $this->assertTrue($this->validator->ip('1.1.1.1'));
        $this->assertTrue($this->validator->ip('fe00::0'));
    }

    public function testUrl() {
        $this->assertFalse($this->validator->url('index.html'));
        $this->assertTrue($this->validator->url('http://localhost/index.html'));
        $this->assertTrue($this->validator->url('https://example.com/index.html'));
        $this->assertTrue($this->validator->url('ftp://example.com/index.html'));
    }

    public function testBlank() {
        $this->assertFalse($this->validator->blank('.'));
        $this->assertTrue($this->validator->blank(''));
        $this->assertTrue($this->validator->blank(null));
        $this->assertTrue($this->validator->blank(' '));
    }

    public function testRequired() {
        $this->assertFalse($this->validator->required(''));
        $this->assertTrue($this->validator->required('data'));
    }

    public function testBoolean() {
        $this->assertTrue($this->validator->boolean('1'));
        $this->assertTrue($this->validator->boolean('on'));
        $this->assertTrue($this->validator->boolean('true'));
        $this->assertTrue($this->validator->boolean('yes'));
        $this->assertFalse($this->validator->boolean('0'));
        $this->assertFalse($this->validator->boolean(''));
        $this->assertFalse($this->validator->boolean('false'));
    }

    public function testString() {
        $this->assertTrue($this->validator->string('hello'));
        $this->assertTrue($this->validator->string('hello', array(
            'min_length' => 3
        )));
        $this->assertTrue($this->validator->string('hello', array(
            'max_length' => 5
        )));
        $this->assertTrue($this->validator->string('hello', array(
            'min_length' => 3,
            'max_length' => 6
        )));
        $this->assertFalse($this->validator->string('user', array(
            'min_length' => 5
        )));
        $this->assertFalse($this->validator->string('user', array(
            'max_length' => 3 
        )));

        $this->assertTrue($this->validator->string('male', array(
            'in_array' => array('male', 'female')
        )));
        $this->assertFalse($this->validator->string('males', array(
            'in_array' => array('male', 'female')
        )));
    }

    public function testNumber() {
        $this->assertTrue($this->validator->number("1.1"));
        $this->assertTrue($this->validator->number("10"));
        $this->assertTrue($this->validator->number(100));
        $this->assertTrue($this->validator->number(-12));
        $this->assertFalse($this->validator->number('s1'));
        $this->assertFalse($this->validator->number('100_11'));

        $this->assertTrue($this->validator->number(5, array(
            'min' => 3,
            'max' => 6
        )));

        $this->assertTrue($this->validator->number(5, array(
            'in_array' => range(1, 10)
        )));
    }

    public function testInteger() {
        $this->assertTrue($this->validator->integer('10'));
        $this->assertTrue($this->validator->integer(12));
        $this->assertFalse($this->validator->integer(12.3));
        $this->assertFalse($this->validator->integer('0.1'));
        $this->assertTrue($this->validator->integer(5, array(
            'min' => 3,
            'max' => 6
        )));
    }

    public function testFloat() {
        $this->assertTrue($this->validator->float(10.0));
        $this->assertTrue($this->validator->float("11.12"));
        $this->assertTrue($this->validator->float(-0.3));
        $this->assertFalse($this->validator->float('11.2d'));
    }

    public function testRegexp() {
        $this->assertTrue($this->validator->regexp('a-b', '/^\w\-\w$/'));
        $this->assertTrue($this->validator->regexp('34', '/^\d+$/'));
    }
}
