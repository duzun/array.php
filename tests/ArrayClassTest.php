<?php

namespace duzun\ArrayClass\Tests;

use duzun\ArrayClass as AC;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * @author DUzun.Me
 *
 * @TODO: Test all methods
 */

class ArrayClassTest extends TestCase {
    use TestLoggerTrait;
    // -----------------------------------------------------
    /** @var bool */
    public static $log = false;

    // -----------------------------------------------------
    public function test_to_array() {
        Assert::assertEquals([123], AC::to_array(123), 'to_array(int)');
        Assert::assertEquals(['string'], AC::to_array('string'), 'to_array(string)');

        $t = [1, '2', 3 => [4, 5]];
        Assert::assertEquals($t, AC::to_array((object) $t), 'to_array(stdClass)');

        $g = function ($x) {
            foreach ($x as $k => $v) {
                yield $k => $v;
            }
        };
        Assert::assertEquals($t, AC::to_array($g($t)), 'to_array(Generator)');
        Assert::assertEquals($t, AC::to_array(new \ArrayObject($t)), 'to_array(ArrayObject)');

        // Special method getArrayCopy()
        $o = new class ($t) {
            private $a;
            public function __construct(array $a) {
                $this->a = $a;
            }
            public function getArrayCopy(): array {
                return $this->a;
            }
        };

        $u = new class () {
            public function getArrayCopy() {
                return;
            }
        };

        $this->assertEquals($t, AC::to_array($o), 'to_array(class::getArrayCopy())');
        $this->assertEquals([], AC::to_array($u), 'to_array(class::getArrayCopy() == null) == []');

        $noa = new class () {};
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
        $indexed = [0, 1, 2, 3, 345, 'any value', ['r' => true]];

        Assert::assertFalse(AC::is_assoc($indexed, false));
        Assert::assertFalse(AC::is_assoc($indexed, true));

        Assert::assertFalse(AC::is_assoc(['a', 'b', 'c']));
        Assert::assertTrue(AC::is_assoc([1 => 'a', 2 => 'b', 3 => 'c']));
        Assert::assertFalse(AC::is_assoc([1 => 'a', 2 => 'b', 3 => 'c'], false));
        Assert::assertTrue(AC::is_assoc(['x' => 'a', 2 => 'b', 3 => 'c'], false));

        $indexed[100] = 'out of order';
        Assert::assertFalse(AC::is_assoc($indexed, false));
        Assert::assertTrue(AC::is_assoc($indexed, true));

        unset($indexed[100], $indexed[0]);
        Assert::assertFalse(AC::is_assoc($indexed, false));
        Assert::assertTrue(AC::is_assoc($indexed, true));

        $indexed[0] = 'put back first value';
        $indexed['string'] = 'key';
        Assert::assertTrue(AC::is_assoc($indexed, true));
        Assert::assertTrue(AC::is_assoc($indexed, false));
    }

    // -----------------------------------------------------
    public function test_group() {
        Assert::assertEquals([], AC::group([], ['a', 'b'], true));
        Assert::assertEquals([], AC::group([], ['a', 'b'], false));

        $a = [
            ['a' => 1, 'b' => 3, 'c' => 7],
            ['a' => 1, 'b' => 3, 'c' => 8],
            ['a' => 1, 'b' => 5, 'c' => 9],
            ['a' => 2, 'b' => 3, 'c' => 10],
            ['a' => 2, 'b' => 5, 'c' => 11],
            ['a' => 2, 'b' => 5, 'c' => 11],
        ];

        // No grouping -> noop
        Assert::assertEquals($a, AC::group($a, [], true));
        Assert::assertEquals($a, AC::group($a, [], false));

        Assert::assertEquals(
            [
                1 => ['a' => 1, 'b' => 5, 'c' => 9],
                2 => ['a' => 2, 'b' => 5, 'c' => 11],
            ],
            AC::group($a, ['a'], false)
        );

        Assert::assertEquals(
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

        Assert::assertEquals(
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

        Assert::assertEquals(
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
                        ['a' => 2, 'b' => 5, 'c' => 11],
                        ['a' => 2, 'b' => 5, 'c' => 11],
                    ],
                ],
            ],
            AC::group($a, ['a', 'b'], true)
        );
    }

    // -----------------------------------------------------
    public function test_id() {
        Assert::assertEquals(1, AC::id(null));
        Assert::assertEquals(1, AC::id([1]));
        Assert::assertEquals(3, AC::id([2 => 1]));
        Assert::assertEquals(12, AC::id([9 => 0, 10 => 1, 11 => 2]));
    }
    // -----------------------------------------------------
    public function test_repeat() {
        Assert::assertEquals([], AC::repeat([1], 0));
        Assert::assertEquals([], AC::repeat([1], -5));
        Assert::assertEquals([1, 1, 1], AC::repeat([1], 3));
        Assert::assertEquals([1, 2, 3, 1, 2, 3, 1, 2, 3], AC::repeat(['a' => 1, 'b' => 2, 'c' => 3], 3));
        Assert::assertEquals([1, 2, 3], AC::repeat(['a' => 1, 'b' => 2, 'c' => 3], 1));
        Assert::assertEquals([1, 2, 3], AC::repeat([1, 2, 3], 1));
    }
    // -----------------------------------------------------
    public function test_cyclic_slice() {
        $array = ['a' => 1, 'b' => 2, 'c' => 3, 3 => 4, 'e' => 5, 9 => 6, 'g' => 7];

        // Trivial cases
        Assert::assertEquals([], AC::cyclic_slice([], 1, 3));
        Assert::assertEquals([], AC::cyclic_slice([], 1, 3, true));
        Assert::assertEquals([], AC::cyclic_slice($array, 0, 0));
        Assert::assertEquals([], AC::cyclic_slice($array, 0, 0, true));

        // Positive length
        Assert::assertEquals([1], AC::cyclic_slice($array, 0, 1));
        Assert::assertEquals([1, 2, 3], AC::cyclic_slice($array, 0, 3));
        Assert::assertEquals([1, 2, 3], AC::cyclic_slice($array, 7, 3));
        Assert::assertEquals([1, 2, 3], AC::cyclic_slice($array, -7, 3));
        Assert::assertEquals([4, 5, 6], AC::cyclic_slice($array, 3, 3));
        Assert::assertEquals([4, 5, 6], AC::cyclic_slice($array, -4, 3));
        Assert::assertEquals([7, 1, 2, 3], AC::cyclic_slice($array, 6, 4));
        Assert::assertEquals([7, 1, 2, 3], AC::cyclic_slice($array, -1, 4));
        Assert::assertEquals([2, 3, 4, 5, 6, 7, 1, 2, 3, 4, 5, 6, 7, 1, 2], AC::cyclic_slice($array, 50, 15));
        Assert::assertEquals([2, 3, 4, 5, 6, 7, 1, 2, 3, 4, 5, 6, 7, 1, 2], AC::cyclic_slice($array, -48, 15));

        // Negative length
        Assert::assertEquals([1], AC::cyclic_slice($array, 0, -1));
        Assert::assertEquals([1, 7, 6], AC::cyclic_slice($array, 0, -3));
        Assert::assertEquals([3, 2, 1], AC::cyclic_slice($array, 2, -3));
        Assert::assertEquals([5, 4, 3, 2, 1, 7, 6, 5, 4], AC::cyclic_slice($array, -3, -9));
        Assert::assertEquals(['c', 'b'], AC::cyclic_slice(['a', 'b', 'c', 'd', 'e'], 2, -2));
        Assert::assertEquals(['b', 'a', 'd', 'c', 'b', 'a', 'd', 'c', 'b'], AC::cyclic_slice(['a', 'b', 'c', 'd'], -3, -9));

        // Shift/Rotate
        Assert::assertEquals([2, 3, 4, 5, 6, 7, 1], AC::cyclic_slice($array, 1));
        Assert::assertEquals([5, 6, 7, 1, 2, 3, 4], AC::cyclic_slice($array, 4));
        Assert::assertEquals([5, 6, 7, 1, 2, 3, 4], AC::cyclic_slice($array, 11));
        Assert::assertEquals([5, 6, 7, 1, 2, 3, 4], AC::cyclic_slice($array, -3));

        // Reverse & Rotate
        Assert::assertEquals([5, 4, 3, 2, 1, 7, 6], AC::cyclic_slice($array, 4, -count($array)));

        // Preserve keys, positive length
        Assert::assertEquals(array_slice($array, 0, 1, true), AC::cyclic_slice($array, 0, 1, true));
        Assert::assertEquals(array_slice($array, 0, 3, true), AC::cyclic_slice($array, 0, 3, true));
        Assert::assertEquals(array_slice($array, 0, 3, true), AC::cyclic_slice($array, 7, 3, true));
        Assert::assertEquals(array_slice($array, 0, 3, true), AC::cyclic_slice($array, -7, 3, true));
        Assert::assertEquals(array_slice($array, 3, 3, true), AC::cyclic_slice($array, 3, 3, true));
        Assert::assertEquals(array_slice($array, -4, 3, true), AC::cyclic_slice($array, -4, 3, true));
        Assert::assertEquals(['g' => 7, 'a' => 1, 'b' => 2, 'c' => 3], AC::cyclic_slice($array, 6, 4, true));
        Assert::assertEquals(['g' => 7, 'a' => 1, 'b' => 2, 'c' => 3], AC::cyclic_slice($array, -1, 4, true));

        // When preserving keys, length can't be greater than that of the $array
        Assert::assertEquals(
            ['b' => 2, 'c' => 3, 3 => 4, 'e' => 5, 9 => 6, 'g' => 7, 'a' => 1],
            AC::cyclic_slice($array, 50, 15, true)
        );
        Assert::assertEquals(
            ['b' => 2, 'c' => 3, 3 => 4, 'e' => 5, 9 => 6, 'g' => 7, 'a' => 1],
            AC::cyclic_slice($array, -48, 15, true)
        );

        // Preserve keys, negative length
        Assert::assertEquals(['a' => 1], AC::cyclic_slice($array, 0, -1, true));
        Assert::assertEquals(['a' => 1, 'g' => 7, 9 => 6], AC::cyclic_slice($array, 0, -3, true));
        Assert::assertEquals(['c' => 3, 'b' => 2, 'a' => 1], AC::cyclic_slice($array, 2, -3, true));
        Assert::assertEquals(
            ['e' => 5, 3 => 4, 'c' => 3, 'b' => 2, 'a' => 1, 'g' => 7, 9 => 6],
            AC::cyclic_slice($array, -3, -9, true)
        );

        // Preserve keys, Shift/Rotate
        Assert::assertEquals(
            ['b' => 2, 'c' => 3, 3 => 4, 'e' => 5, 9 => 6, 'g' => 7, 'a' => 1],
            AC::cyclic_slice($array, 1, null, true)
        );
        Assert::assertEquals(
            ['e' => 5, 9 => 6, 'g' => 7, 'a' => 1, 'b' => 2, 'c' => 3, 3 => 4],
            AC::cyclic_slice($array, 4, null, true)
        );
        Assert::assertEquals(
            ['e' => 5, 9 => 6, 'g' => 7, 'a' => 1, 'b' => 2, 'c' => 3, 3 => 4],
            AC::cyclic_slice($array, 11, null, true)
        );
        Assert::assertEquals(
            ['e' => 5, 9 => 6, 'g' => 7, 'a' => 1, 'b' => 2, 'c' => 3, 3 => 4],
            AC::cyclic_slice($array, -3, null, true)
        );
    }
    // -----------------------------------------------------
    public function test_sample() {
        $arr = range(0, 1000, 1);

        $s = AC::sample($arr, 0.9543);
        // should return a sample
        Assert::assertNotEmpty($s);
        // should have only elements from source
        Assert::assertEmpty(array_diff($s, $arr));
        // sample should return any element at most once
        Assert::assertEquals(array_unique($s), $s);
        // should not return everything
        Assert::assertLessThan(count($arr), count($s));

        // should work for small integer sizes
        Assert::assertEquals(0, count(AC::sample($arr, 0)));
        Assert::assertEquals(1, count(AC::sample($arr, 1.0)));
        Assert::assertEquals(2, count(AC::sample($arr, 2)));
        Assert::assertEquals(2, count(AC::sample($arr, 2.1)));

        // round up
        Assert::assertEquals($arr, AC::sample($arr, .999999));
        Assert::assertEquals([reset($arr)], AC::sample($arr, .000001));

        // should return at most the source
        Assert::assertEquals($arr, AC::sample($arr, count($arr)));
        Assert::assertEquals($arr, AC::sample($arr, count($arr) + 1));

        // sample should be stable
        $s1 = AC::sample($arr, 0.721);
        $s2 = AC::sample($arr, 0.721);
        Assert::assertEquals($s1, $s2);

        $s1 = AC::sample($arr, 123);
        $s2 = AC::sample($arr, 123);
        Assert::assertEquals($s1, $s2);

        // Sample using a validator function:
        $vm = new ValidatorMock();

        $s = AC::sample($arr, 0.1, $vm->is_inter());
        Assert::assertTrue($s);
        Assert::assertGreaterThanOrEqual(count($arr) * 0.1, $vm->calls);

        $s = AC::sample($arr, 1, $vm->is_inter());
        Assert::assertTrue($s);
        Assert::assertEquals(1, $vm->calls);

        $s = AC::sample($arr, 0, $vm->falser());
        Assert::assertTrue($s);
        Assert::assertEquals(0, $vm->calls);

        $s = AC::sample($arr, 1, $vm->falser());
        Assert::assertFalse($s);
        Assert::assertEquals(1, $vm->calls);

        $s = AC::sample($arr, .999999, $vm->is_inter());
        Assert::assertTrue($s);
        Assert::assertEquals(count($arr), $vm->calls);

        // Sample using a validator with values
        $s = AC::sample($arr, 0.11, $vm->is_evener());
        Assert::assertEquals(2, count($s));
        Assert::assertArrayHasKey('odd', $s);
        Assert::assertArrayHasKey('even', $s);
        Assert::assertEquals(1, array_sum($s));
        Assert::assertGreaterThanOrEqual(intval(count($arr) * 0.11), $vm->calls);

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
        Assert::assertEquals([
            'NULL',
            'array',
            'boolean',
            'double',
            'integer',
            'object',
            'string',
        ], $s);

        // Empty case
        Assert::assertEquals([], AC::sample([], 0));
        Assert::assertEquals(true, AC::sample([], 0, $vm->is_inter()));

        Assert::assertEquals([], AC::sample([], 0.9));
        Assert::assertEquals(true, AC::sample([], 0.9, $vm->is_inter()));

        Assert::assertEquals([], AC::sample([], 1));
        Assert::assertEquals(true, AC::sample([], 1, $vm->is_inter()));

    }
    // -----------------------------------------------------
    // -----------------------------------------------------

}
