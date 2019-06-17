<?php
use duzun\ArrayClass as AC;

// -----------------------------------------------------
/**
 *  @author DUzun.Me
 *
 *  @TODO: Test all methods
 */
// -----------------------------------------------------
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . '_PHPUnit_BaseClass.php';

// -----------------------------------------------------

class TestArray extends PHPUnit_BaseClass {
    // -----------------------------------------------------
    public static $log       = true;
    public static $className = 'duzun\Array';

    // Before any test
    public static function mySetUpBeforeClass() {
    }

    // After all tests
    public static function myTearDownAfterClass() {
    }

    // -----------------------------------------------------
    public function test_to_array() {
        $this->assertEquals([123], AC::to_array(123), 'to_array(int)');
        $this->assertEquals(['string'], AC::to_array('string'), 'to_array(string)');

        $t = [ 1, '2', 3 => [4, 5]];
        $this->assertEquals($t, AC::to_array((object)$t), 'to_array(stdClass)');

        $g = function ($x) {
            foreach($x as $k => $v) {
                yield $k => $v;
            }
        };
        $this->assertEquals($t, AC::to_array($g($t)), 'to_array(Generator)');
        $this->assertEquals($t, AC::to_array(new \ArrayObject($t)), 'to_array(ArrayObject)');

        // Special method getArrayCopy()
        $o = new class($t) {
            private $a;
            public function __construct(array $a) {
                $this->a = $a;
            }
            public function getArrayCopy(): array {
                return $this->a;
            }
        };

        $u = new class() {
            public function getArrayCopy() {
                return;
            }
        };

        $this->assertEquals($t, AC::to_array($o), 'to_array(class::getArrayCopy())');
        $this->assertEquals([], AC::to_array($u), 'to_array(class::getArrayCopy() == null) == []');

        $noa = new class {};
        $this->assertEquals($noa, AC::to_array($noa), 'to_array(class)');

        $g = function () {
            yield 0 => 1;
            yield 1 => 2;
            yield 1 => 3;
            yield 0 => 4;
        };
        $this->assertEquals([1, 2, 3, 4], AC::to_array($g()), 'to_array(Generator with repeated keys)');
    }

    // -----------------------------------------------------
    public function test_is_assoc() {
        $indexed = array(0, 1, 2, 3, 345, 'any value', array('r' => true));

        $this->assertFalse(AC::is_assoc($indexed, false));
        $this->assertFalse(AC::is_assoc($indexed, true));

        $indexed[100] = 'out of order';
        $this->assertFalse(AC::is_assoc($indexed, false));
        $this->assertTrue(AC::is_assoc($indexed, true));

        unset($indexed[100], $indexed[0]);
        $this->assertFalse(AC::is_assoc($indexed, false));
        $this->assertTrue(AC::is_assoc($indexed, true));

        $indexed[0] = 'put back first value';
        $indexed['string'] = 'key';
        $this->assertTrue(AC::is_assoc($indexed, true));
        $this->assertTrue(AC::is_assoc($indexed, false));
    }

    // -----------------------------------------------------
    public function test_group() {
        $a = [
            [ 'a' => 1, 'b' => 3, 'c' => 7],
            [ 'a' => 1, 'b' => 3, 'c' => 8],
            [ 'a' => 1, 'b' => 5, 'c' => 9],
            [ 'a' => 2, 'b' => 3, 'c' => 10],
            [ 'a' => 2, 'b' => 5, 'c' => 11],
            [ 'a' => 2, 'b' => 5, 'c' => 11],
        ];

        $this->assertEquals(
            [
                1 => [
                  3 => [ 'a' => 1, 'b' => 3, 'c' => 8, ],
                  5 => [ 'a' => 1, 'b' => 5, 'c' => 9, ],
                ],
                2 => [
                  3 => [ 'a' => 2, 'b' => 3, 'c' => 10, ],
                  5 => [ 'a' => 2, 'b' => 5, 'c' => 11, ],
                ],
            ]
          , AC::group($a, ['a', 'b'], false)
        );

        $this->assertEquals(
            [
                1 => [
                  3 => [
                    [ 'a' => 1, 'b' => 3, 'c' => 7, ],
                    [ 'a' => 1, 'b' => 3, 'c' => 8, ],
                  ],
                  5 => [
                    [ 'a' => 1, 'b' => 5, 'c' => 9, ],
                  ],
                ],
                2 => [
                  3 => [
                    [ 'a' => 2, 'b' => 3, 'c' => 10, ],
                  ],
                  5 => [
                    [ 'a' => 2, 'b' => 5, 'c' => 11, ],
                    [ 'a' => 2, 'b' => 5, 'c' => 11, ],
                  ],
                ],
            ]
          , AC::group($a, ['a', 'b'], true)
        );

    }

    // -----------------------------------------------------
    public function test_id() {
        $this->assertEquals(1, AC::id(NULL));
        $this->assertEquals(1, AC::id([1]));
        $this->assertEquals(3, AC::id([2=>1]));
        $this->assertEquals(12, AC::id([9=>0, 10=>1, 11=>2]));
    }
    // -----------------------------------------------------
    // -----------------------------------------------------

}
