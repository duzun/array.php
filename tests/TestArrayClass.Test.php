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

class TestArrayClass extends PHPUnit_BaseClass
{
    // -----------------------------------------------------
    public static $log       = true;
    public static $className = 'duzun\Array';

    // Before any test
    public static function mySetUpBeforeClass() {}

    // After all tests
    public static function myTearDownAfterClass() {}

    // -----------------------------------------------------
    public function test_to_array()
    {
        $this->assertEquals([123], AC::to_array(123), 'to_array(int)');
        $this->assertEquals(['string'], AC::to_array('string'), 'to_array(string)');

        $t = [1, '2', 3 => [4, 5]];
        $this->assertEquals($t, AC::to_array((object) $t), 'to_array(stdClass)');

        $g = function ($x) {
            foreach ($x as $k => $v) {
                yield $k => $v;
            }
        };
        $this->assertEquals($t, AC::to_array($g($t)), 'to_array(Generator)');
        $this->assertEquals($t, AC::to_array(new \ArrayObject($t)), 'to_array(ArrayObject)');

        // Special method getArrayCopy()
        $o = new class($t)
        {
            private $a;
            public function __construct(array $a)
            {
                $this->a = $a;
            }
            public function getArrayCopy(): array
            {
                return $this->a;
            }
        };

        $u = new class()
        {
            public function getArrayCopy()
            {
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
    public function test_is_assoc()
    {
        $indexed = array(0, 1, 2, 3, 345, 'any value', array('r' => true));

        $this->assertFalse(AC::is_assoc($indexed, false));
        $this->assertFalse(AC::is_assoc($indexed, true));

        $this->assertFalse(AC::is_assoc(['a', 'b', 'c']));
        $this->assertTrue(AC::is_assoc([1 => 'a', 2 => 'b', 3 => 'c']));
        $this->assertFalse(AC::is_assoc([1 => 'a', 2 => 'b', 3 => 'c'], false));
        $this->assertTrue(AC::is_assoc(['x' => 'a', 2 => 'b', 3 => 'c'], false));

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
    public function test_group()
    {
        $this->assertEquals([], AC::group([], ['a', 'b'], true));
        $this->assertEquals([], AC::group([], ['a', 'b'], false));

        $a = [
            ['a' => 1, 'b' => 3, 'c' => 7],
            ['a' => 1, 'b' => 3, 'c' => 8],
            ['a' => 1, 'b' => 5, 'c' => 9],
            ['a' => 2, 'b' => 3, 'c' => 10],
            ['a' => 2, 'b' => 5, 'c' => 11],
            ['a' => 2, 'b' => 5, 'c' => 11],
        ];

        // No grouping -> noop
        $this->assertEquals($a, AC::group($a, [], true));
        $this->assertEquals($a, AC::group($a, [], false));

        $this->assertEquals(
            [
                1 => ['a' => 1, 'b' => 5, 'c' => 9],
                2 => ['a' => 2, 'b' => 5, 'c' => 11],
            ],
            AC::group($a, ['a'], false)
        );

        $this->assertEquals(
            [
                1 => [
                    ['a' => 1, 'b' => 3, 'c' => 7],
                    ['a' => 1, 'b' => 3, 'c' => 8],
                    ['a' => 1, 'b' => 5, 'c' => 9],
                ],
                2 => [
                    ['a' => 2, 'b' => 3, 'c' => 10],
                    ['a' => 2, 'b' => 5, 'c' => 11],
                    ['a' => 2, 'b' => 5, 'c' => 11],
                ],
            ],
            AC::group($a, ['a'], true)
        );

        $this->assertEquals(
            [
                1 => [
                    3 => ['a' => 1, 'b' => 3, 'c' => 8],
                    5 => ['a' => 1, 'b' => 5, 'c' => 9],
                ],
                2 => [
                    3 => ['a' => 2, 'b' => 3, 'c' => 10],
                    5 => ['a' => 2, 'b' => 5, 'c' => 11],
                ],
            ],
            AC::group($a, ['a', 'b'], false)
        );

        $this->assertEquals(
            [
                1 => [
                    3 => [
                        ['a' => 1, 'b' => 3, 'c' => 7],
                        ['a' => 1, 'b' => 3, 'c' => 8],
                    ],
                    5 => [
                        ['a' => 1, 'b' => 5, 'c' => 9],
                    ],
                ],
                2 => [
                    3 => [
                        ['a' => 2, 'b' => 3, 'c' => 10],
                    ],
                    5 => [
                        ['a' => 2, 'b' => 5, 'c' => 11,],
                        ['a' => 2, 'b' => 5, 'c' => 11,],
                    ],
                ],
            ],
            AC::group($a, ['a', 'b'], true)
        );
    }

    // -----------------------------------------------------
    public function test_id()
    {
        $this->assertEquals(1, AC::id(NULL));
        $this->assertEquals(1, AC::id([1]));
        $this->assertEquals(3, AC::id([2 => 1]));
        $this->assertEquals(12, AC::id([9 => 0, 10 => 1, 11 => 2]));
    }
    // -----------------------------------------------------
    public function test_repeat()
    {
        $this->assertEquals([], AC::repeat([1], 0));
        $this->assertEquals([], AC::repeat([1], -5));
        $this->assertEquals([1, 1, 1], AC::repeat([1], 3));
        $this->assertEquals([1, 2, 3, 1, 2, 3, 1, 2, 3], AC::repeat(['a' => 1, 'b' => 2, 'c' => 3], 3));
        $this->assertEquals([1, 2, 3], AC::repeat(['a' => 1, 'b' => 2, 'c' => 3], 1));
        $this->assertEquals([1, 2, 3], AC::repeat([1, 2, 3], 1));
    }
    // -----------------------------------------------------
    public function test_cyclic_slice()
    {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 3 => 4, 'e' => 5, 9 => 6, 'g' => 7];

        // Trivial cases
        $this->assertEquals([], AC::cyclic_slice([], 1, 3));
        $this->assertEquals([], AC::cyclic_slice([], 1, 3, true));
        $this->assertEquals([], AC::cyclic_slice($array, 0, 0));
        $this->assertEquals([], AC::cyclic_slice($array, 0, 0, true));

        // Positive length
        $this->assertEquals([1], AC::cyclic_slice($array, 0, 1));
        $this->assertEquals([1, 2, 3], AC::cyclic_slice($array, 0, 3));
        $this->assertEquals([1, 2, 3], AC::cyclic_slice($array, 7, 3));
        $this->assertEquals([1, 2, 3], AC::cyclic_slice($array, -7, 3));
        $this->assertEquals([4, 5, 6], AC::cyclic_slice($array, 3, 3));
        $this->assertEquals([4, 5, 6], AC::cyclic_slice($array, -4, 3));
        $this->assertEquals([7, 1, 2, 3], AC::cyclic_slice($array, 6, 4));
        $this->assertEquals([7, 1, 2, 3], AC::cyclic_slice($array, -1, 4));
        $this->assertEquals([2, 3, 4, 5, 6, 7, 1, 2, 3, 4, 5, 6, 7, 1, 2], AC::cyclic_slice($array, 50, 15));
        $this->assertEquals([2, 3, 4, 5, 6, 7, 1, 2, 3, 4, 5, 6, 7, 1, 2], AC::cyclic_slice($array, -48, 15));

        // Negative length
        $this->assertEquals([1], AC::cyclic_slice($array, 0, -1));
        $this->assertEquals([1, 7, 6], AC::cyclic_slice($array, 0, -3));
        $this->assertEquals([3, 2, 1], AC::cyclic_slice($array, 2, -3));
        $this->assertEquals([5, 4, 3, 2, 1, 7, 6, 5, 4], AC::cyclic_slice($array, -3, -9));
        $this->assertEquals(['c', 'b'], AC::cyclic_slice(['a', 'b', 'c', 'd', 'e'], 2, -2));
        $this->assertEquals(['b', 'a', 'd', 'c', 'b', 'a', 'd', 'c', 'b'], AC::cyclic_slice(['a', 'b', 'c', 'd'], -3, -9));

        // Shift/Rotate
        $this->assertEquals([2, 3, 4, 5, 6, 7, 1], AC::cyclic_slice($array, 1));
        $this->assertEquals([5, 6, 7, 1, 2, 3, 4], AC::cyclic_slice($array, 4));
        $this->assertEquals([5, 6, 7, 1, 2, 3, 4], AC::cyclic_slice($array, 11));
        $this->assertEquals([5, 6, 7, 1, 2, 3, 4], AC::cyclic_slice($array, -3));

        // Reverse & Rotate
        $this->assertEquals([5, 4, 3, 2, 1, 7, 6], AC::cyclic_slice($array, 4, -count($array)));

        // Preserve keys, positive length
        $this->assertEquals(array_slice($array, 0, 1, true), AC::cyclic_slice($array, 0, 1, true));
        $this->assertEquals(array_slice($array, 0, 3, true), AC::cyclic_slice($array, 0, 3, true));
        $this->assertEquals(array_slice($array, 0, 3, true), AC::cyclic_slice($array, 7, 3, true));
        $this->assertEquals(array_slice($array, 0, 3, true), AC::cyclic_slice($array, -7, 3, true));
        $this->assertEquals(array_slice($array, 3, 3, true), AC::cyclic_slice($array, 3, 3, true));
        $this->assertEquals(array_slice($array, -4, 3, true), AC::cyclic_slice($array, -4, 3, true));
        $this->assertEquals(['g' => 7, 'a' => 1, 'b' => 2, 'c' => 3], AC::cyclic_slice($array, 6, 4, true));
        $this->assertEquals(['g' => 7, 'a' => 1, 'b' => 2, 'c' => 3], AC::cyclic_slice($array, -1, 4, true));

        // When preserving keys, length can't be greater than that of the $array
        $this->assertEquals(
            ['b' => 2, 'c' => 3, 3 => 4, 'e' => 5, 9 => 6, 'g' => 7, 'a' => 1],
            AC::cyclic_slice($array, 50, 15, true)
        );
        $this->assertEquals(
            ['b' => 2, 'c' => 3, 3 => 4, 'e' => 5, 9 => 6, 'g' => 7, 'a' => 1],
            AC::cyclic_slice($array, -48, 15, true)
        );

        // Preserve keys, negative length
        $this->assertEquals(['a' => 1], AC::cyclic_slice($array, 0, -1, true));
        $this->assertEquals(['a' => 1, 'g' => 7, 9 => 6], AC::cyclic_slice($array, 0, -3, true));
        $this->assertEquals(['c' => 3, 'b' => 2, 'a' => 1], AC::cyclic_slice($array, 2, -3, true));
        $this->assertEquals(
            ['e' => 5, 3 => 4, 'c' => 3, 'b' => 2, 'a' => 1, 'g' => 7, 9 => 6],
            AC::cyclic_slice($array, -3, -9, true)
        );

        // Preserve keys, Shift/Rotate
        $this->assertEquals(
            ['b' => 2, 'c' => 3, 3 => 4, 'e' => 5, 9 => 6, 'g' => 7, 'a' => 1],
            AC::cyclic_slice($array, 1, null, true)
        );
        $this->assertEquals(
            ['e' => 5, 9 => 6, 'g' => 7, 'a' => 1, 'b' => 2, 'c' => 3, 3 => 4],
            AC::cyclic_slice($array, 4, null, true)
        );
        $this->assertEquals(
            ['e' => 5, 9 => 6, 'g' => 7, 'a' => 1, 'b' => 2, 'c' => 3, 3 => 4],
            AC::cyclic_slice($array, 11, null, true)
        );
        $this->assertEquals(
            ['e' => 5, 9 => 6, 'g' => 7, 'a' => 1, 'b' => 2, 'c' => 3, 3 => 4],
            AC::cyclic_slice($array, -3, null, true)
        );
    }
    // -----------------------------------------------------
    public function test_sample()
    {
        $arr = range(0, 1000, 1);

        $s = AC::sample($arr, 0.9543);
        // should return a sample
        $this->assertNotEmpty($s);
        // should have only elements from source
        $this->assertEmpty(array_diff($s, $arr));
        // sample should return any element at most once
        $this->assertEquals(array_unique($s), $s);
        // should not return everything
        $this->assertLessThan(count($arr), count($s));

        // should work for small integer sizes
        $this->assertEquals(0, count(AC::sample($arr, 0)));
        $this->assertEquals(1, count(AC::sample($arr, 1.0)));
        $this->assertEquals(2, count(AC::sample($arr, 2)));
        $this->assertEquals(2, count(AC::sample($arr, 2.1)));

        // round up
        $this->assertEquals($arr, AC::sample($arr, .999999));
        $this->assertEquals([reset($arr)], AC::sample($arr, .000001));

        // should return at most the source
        $this->assertEquals($arr, AC::sample($arr, count($arr)));
        $this->assertEquals($arr, AC::sample($arr, count($arr) + 1));

        // sample should be stable
        $s1 = AC::sample($arr, 0.721);
        $s2 = AC::sample($arr, 0.721);
        $this->assertEquals($s1, $s2);

        $s1 = AC::sample($arr, 123);
        $s2 = AC::sample($arr, 123);
        $this->assertEquals($s1, $s2);

        // Sample using a validator function:
        $vm = new ValidatorMock();

        $s = AC::sample($arr, 0.1, $vm->is_inter());
        $this->assertTrue($s);
        $this->assertGreaterThanOrEqual(count($arr) * 0.1, $vm->calls);

        $s = AC::sample($arr, 1, $vm->is_inter());
        $this->assertTrue($s);
        $this->assertEquals(1, $vm->calls);

        $s = AC::sample($arr, 0, $vm->falser());
        $this->assertTrue($s);
        $this->assertEquals(0, $vm->calls);

        $s = AC::sample($arr, 1, $vm->falser());
        $this->assertFalse($s);
        $this->assertEquals(1, $vm->calls);

        $s = AC::sample($arr, .999999, $vm->is_inter());
        $this->assertTrue($s);
        $this->assertEquals(count($arr), $vm->calls);

        // Sample using a validator with values
        $s = AC::sample($arr, 0.11, $vm->is_evener());
        $this->assertEquals(2, count($s));
        $this->assertEmpty(array_diff_key($s, ['even' => 1, 'odd' => 1]));
        $this->assertEquals(1, array_sum($s));
        $this->assertGreaterThanOrEqual(intval(count($arr) * 0.11), $vm->calls);

        // Get the types of the array elements
        $s = array_keys(
            AC::sample(
                [0, 1.0, 'a', true, null, [], $this],
                0.999,
                function ($v) {
                    return gettype($v);
                }
            )
        );
        sort($s);
        $this->assertEquals([
            'NULL',
            'array',
            'boolean',
            'double',
            'integer',
            'object',
            'string',
        ], $s);

        // Empty case
        $this->assertEquals([], AC::sample([], 0));
        $this->assertEquals(true, AC::sample([], 0, $vm->is_inter()));

        $this->assertEquals([], AC::sample([], 0.9));
        $this->assertEquals(true, AC::sample([], 0.9, $vm->is_inter()));

        $this->assertEquals([], AC::sample([], 1));
        $this->assertEquals(true, AC::sample([], 1, $vm->is_inter()));


    }
    // -----------------------------------------------------
    // -----------------------------------------------------

}


class ValidatorMock
{
    public $calls = 0;

    function reset()
    {
        $this->calls = 0;
    }

    function falser()
    {
        return $this->wrap(function () {
            return false;
        });
    }

    function truer()
    {
        return $this->wrap(function () {
            return true;
        });
    }

    function is_inter()
    {
        return $this->wrap(function ($v) {
            return is_int($v);
        });
    }

    function is_evener()
    {
        return $this->wrap(function ($v) {
            return ($v & 1) ? 'odd' : 'even';
        });
    }

    function gettyper()
    {
        return $this->wrap(function ($v) {
            return gettype($v);
        });
    }

    function wrap(callable $callable)
    {
        $this->reset();
        return function (...$args) use ($callable) {
            $this->calls++;
            return $callable(...$args);
        };
    }
}
