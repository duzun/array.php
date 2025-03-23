# array.php

Useful array methods for PHP

```php
use duzun\ArrayClass as AC;
```

## Methods

### ::to_array

Try to convert a value into Array, or return the value when can't convert.

```php
ArrayClass::to_array($object, $recursive=false)
```

The `$object` can be a `Generator`, `Traversable` or any object which has a `::getArrayCopy()` method.

##### Examples

```php
AC::to_array(1); // [1]
AC::to_array((function() { yield 1; })()); // [1]
AC::to_array(new \ArrayObject([1,2,3])); // [1,2,3]
AC::to_array($o = new class {}); // $o - don't know how to convert :(
```

### ::is_assoc

Check whether an array is associative or not (indexed?).

```php
ArrayClass::is_assoc($array, $strict = true): bool
```

##### Examples

```php
AC::is_assoc(['a','b','c']) === false;
AC::is_assoc([1 => 'a', 2 => 'b', 3 => 'c']) === true;
AC::is_assoc([1 => 'a', 2 => 'b', 3 => 'c'], false) === false;
AC::is_assoc(['x' => 'a', 2 => 'b', 3 => 'c'], false) === true;
```

Note: In strict mode, this is the same as `!array_is_list($array)`.

### ::repeat

Repeat the values of an array a number of times (`array_fill()` on steroids).

```php
ArrayClass::repeat(array $array, int $times): array
```

##### Examples

```php
// the most trivial case, same as array_fill(0, 3, 1);
AC::repeat([1], 3) === [1,1,1];
AC::repeat([1,2], 2) === [1,2,1,2];

// $times has to be positive
AC::repeat([1], 0) === [];
AC::repeat([1], -5) === [];

// doesn't preserve keys, ever
AC::repeat(['a' => 1, 'b' => 2, 'c' => 3], 3) === [1,2,3,1,2,3,1,2,3];
AC::repeat(['a' => 1, 'b' => 2, 'c' => 3], 1) === [1,2,3];
```

### ::cyclic_slice

Like `array_slice()`, only cyclic, as if the array was a ring and we can slice from any point any number of items, sequentially.

```php
ArrayClass::cyclic_slice(
    array $array,
    int $offset,
    int $length = NULL,
    bool $preserve_keys = false
): array
```

##### Examples

```php
AC::cyclic_slice(['a','b','c','d','e'], 1) === ['b','c','d','e','a']; // rotate by 1
AC::cyclic_slice(['a','b','c','d','e'], -1) === ['e','a','b','c','d']; // rotate by -1
AC::cyclic_slice(['a','b','c','d','e'], 1, 2) === ['b','c']; // slice 2 items
AC::cyclic_slice(['a','b','c','d','e'], 1, -2) === ['b','a']; // slice 2 items in reverse

// slice, while cycling, to fulfill the length
AC::cyclic_slice(['a','b','c','d'], 3, 9) === ['d','a','b','c','d','a','b','c','d'];
AC::cyclic_slice(['a','b','c','d'], -3, -9) === ['b','a','d','c','b','a','d','c','b'];

// preserve keys
AC::cyclic_slice(['a'=>1, 'b'=>2, 'c'=>3, 'd'=>4], 3, 3) === ['d'=>4, 'a'=>1, 'b'=>2];
```

### ::id

Given a list of items by IDs, get a new ID that doesn't exist in the list.

```php
ArrayClass::id($array): int
```

##### Examples

```php
AC::id( NULL )                 === 1;
AC::id( [1] )                  === 1;
AC::id( [2=>1] )               === 3;
AC::id( [9=>0, 10=>1, 11=>2] ) === 12;
```

### ::group

Group array items of an array by a list of fields.

```php
ArrayClass::group($list, $fields, $as_list = false): array
```

##### Examples

```php
// Given an array of arrays:
$array = [
    [ 'a' => 1, 'b' => 3, 'c' => 7 ],
    [ 'a' => 1, 'b' => 3, 'c' => 8 ],
    [ 'a' => 1, 'b' => 5, 'c' => 9 ],
    [ 'a' => 2, 'b' => 3, 'c' => 10 ],
    [ 'a' => 2, 'b' => 5, 'c' => 11 ],
    [ 'a' => 2, 'b' => 5, 'c' => 11 ],
];

// group by values of field 'a', preserving the last appearance
AC::group($array, ['a']) == [
    1 => [ 'a' => 1, 'b' => 5, 'c' => 9 ],
    2 => [ 'a' => 2, 'b' => 5, 'c' => 11 ],
];

// group by values of field 'a', preserving all items by adding an extra level of depth.
AC::group($array, ['a'], true) == [
    1 => [
        [ 'a' => 1, 'b' => 3, 'c' => 7 ],
        [ 'a' => 1, 'b' => 3, 'c' => 8 ],
        [ 'a' => 1, 'b' => 5, 'c' => 9 ],
    ],
    2 => [
        [ 'a' => 2, 'b' => 3, 'c' => 10 ],
        [ 'a' => 2, 'b' => 5, 'c' => 11 ],
        [ 'a' => 2, 'b' => 5, 'c' => 11 ],
    ],
];

// group by two fields, preserving the last appearance
AC::group($array, ['a', 'b'], false) == [
    1 => [
        3 => [ 'a' => 1, 'b' => 3, 'c' => 8 ],
        5 => [ 'a' => 1, 'b' => 5, 'c' => 9 ],
    ],
    2 => [
        3 => [ 'a' => 2, 'b' => 3, 'c' => 10 ],
        5 => [ 'a' => 2, 'b' => 5, 'c' => 11 ],
    ],
];
```

### ::sample

Get a sample of a given size of an array or validate a sample or its elements with a predicate.

```php
ArrayClass::sample($arr, $size, callable $validate = null): array|bool
```

##### Examples

```php
$array = [1, 2, 3, 4, ..., 1000];

// get a sample of 10% of $array
AC::sample([$arr], 0.10) == [1, 10, 20, ..., 990, 1000];

// get a sample of 3 element of $array
AC::sample([$arr], 3) == [1, 500, 1000];

// validate the array has only (int)s, with some probability
AC::sample([$arr], 0.25, function ($v) { return is_int($v); }) == true;

// get the types of the array elements with proportion
AC::sample(
    [0, 1.0, 'a', true, null, [], $this],
    0.999,
    function ($v) { return gettype($v); }
) == [
    'integer' => 0.14285714285714285,
    'double' => 0.14285714285714285,
    'string' => 0.14285714285714285,
    'boolean' => 0.14285714285714285,
    'NULL' => 0.14285714285714285,
    'array' => 0.14285714285714285,
    'object' => 0.14285714285714285,
];

```

#### See other methods in the [code](https://github.com/duzun/array.php/blob/master/src/ArrayClass.php) and [test](https://github.com/duzun/array.php/blob/master/tests/ArrayClassTest.php) files

@TODO test and document everything else
