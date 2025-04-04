<?php

namespace duzun;

class ArrayClass {
    /**
     * Try to convert a value into Array.
     *
     * @param  mixed  $obj        Any value that can be interpreted as an Array.
     * @param  boolean $recursive If true, do it recursively
     * @return array|object The Array representation of $obj, unless $obj is an
     *                      object without a known method to extract array from it,
     *                      in which case $obj is returned as is.
     */
    public static function to_array($obj, $recursive = false) {
        if (is_array($obj)) {
            if ($recursive && $obj) {
                $ret = [];
                foreach ($obj as $k => $v) {
                    $ret[$k] = static::to_array($v, $recursive);
                }
                $obj = $ret;
            }
            return $obj;
        }

        if (is_object($obj)) {
            if ($obj instanceof \stdClass) {
                $obj = (array) $obj;
            }
            elseif (method_exists($obj, 'getRawData') && is_callable([$obj, 'getRawData'])) {
                $obj = $obj->getRawData();
                isset($obj) or $obj = []; // make sure the result is an array when not set
            }
            elseif (method_exists($obj, 'getArrayCopy') && is_callable([$obj, 'getArrayCopy'])) {
                $obj = $obj->getArrayCopy();
                isset($obj) or $obj = []; // make sure the result is an array when not set
            }
            elseif ($obj instanceof \Generator) {
                $idx = $asoc = [];
                foreach ($obj as $k => $v) {
                    $idx[] = $asoc[$k] = $v;
                }
                // If the generator $obj yielded the same key twice, ignore keys altogether
                $obj = count($idx) > count($asoc) ? $idx : $asoc;
                unset($idx, $asoc); // free mem
            }
            elseif ($obj instanceof \Traversable) {
                $obj = iterator_to_array($obj);
            }
            else {
                return $obj;
            }

            return $recursive ? static::to_array($obj) : $obj;
        }

        return (array) $obj;
    }

    // -------------------------------------------------------------------------
    /**
     * Check whether an array is associative or not (indexed?)
     *
     * In strict mode, this is the same as `!array_is_list($array)`
     *
     * @param array   $array
     * @param boolean $strict If true, require $array's keys to be 0,1,2..., otherwise return false.
     * @return bool
     */
    public static function is_assoc($array, $strict = true): bool {
        if ($strict) {
            static $has_array_is_list = null;
            isset($has_array_is_list) or $has_array_is_list = version_compare(PHP_VERSION, '8.1', '>=');
            if ($has_array_is_list) {
                return !array_is_list($array);
            }

            $i = 0;
            foreach ($array as $k => $v) {
                if ($k !== $i) {
                    return true;
                }

                ++$i;
            }
        }
        else {
            foreach ($array as $k => $v) {
                if (!is_int($k)) {
                    return true;
                }
            }
        }
        return false;
    }
    // -------------------------------------------------------------------------
    /**
     * Group array items of an array by a list of fields.
     *
     * @param  array  $list    A list of (associative) arrays
     * @param  array  $fields  List of fields/keys of values stored in $list.
     * @param  boolean $as_list If FALSE, array values are stored in resulting array
     *                          by key path of $fields. In case some values have same key path,
     *                          the last value is used.
     *                          If TRUE, key path of $fields in resulting array is a list
     *                          of all values that match that key path.
     *
     * @return array   Multidimensional array with key path specified by $fields
     */
    public static function group($list, $fields, bool $as_list = false) {

        if (empty($list) || empty($fields)) {
            return $list;
        }

        $ret = [];
        $oel = null; // define $oel
        foreach ($list as $row) {
            $gf = $fields;
            $first = true;
            $lvl = 0;
            foreach ($gf as $fld) {
                $lvl++;
                $val = $row[$fld];

                if ($first) {
                    $oel = &$ret[$val];
                    $first = false;
                }
                else {
                    $oel = &$oel[$val];
                }
            }
            if ($as_list) {
                $oel[] = $row;
            }
            else {
                $oel = $row;
            }
        }
        unset($oel); // break ref

        return $ret;
    }

    // -------------------------------------------------------------------------
    /**
     * Given a list of items by IDs, get a new ID that doesn't exist in the list.
     *
     * @param  array $arr A list of items with IDs as keys
     * @return int   a new ID to be added to the list
     */
    public static function id($arr) {
        if (is_null($arr)) {
            return 1;
        }
        $id = key($arr) and ++$id;
        if ($id === false) {
            end($arr);
            $id = key($arr) + 1;
        }
        $id = max($id, count($arr)) or ++$id;
        while (isset($arr[$id])) {
            ++$id;
        }

        return $id;
    }

    // ---------------------------------------------------------------
    public static function trim($arr, $chr = null) {
        $ret = [];
        if (isset($chr)) {
            foreach ($arr as $k => $v) {
                $ret[$k] = is_string($v)
                    ? trim($v, $chr)
                    : (is_array($v) ? static::trim($v, $chr) : $v);
            }
        }
        else {
            foreach ($arr as $k => $v) {
                $ret[$k] = is_string($v)
                    ? trim($v)
                    : (is_array($v) ? static::trim($v) : $v);
            }
        }

        return $ret;
    }

    // -------------------------------------------------------------------------
    public static function select($arr, $keys, $force_null = false) {
        $ret = [];
        is_array($keys) or is_object($keys) or $keys = [$keys];
        foreach ($keys as $k) {
            if (isset($arr[$k])) {
                $ret[$k] = $arr[$k];
            }
            elseif ($force_null) {
                $ret[$k] = null;
            }
        }
        return $ret;
    }

    public static function select_ref(&$arr, $keys, $force_null = false) {
        $ret = [];
        is_array($keys) or is_object($keys) or $keys = [$keys];
        foreach ($keys as $k) {
            if (isset($arr[$k])) {
                $ret[$k] = &$arr[$k];
            }
            elseif ($force_null) {
                $ret[$k] = null;
            }
        }
        return $ret;
    }

    public static function select_pref($arr, $pref) {
        $len = strlen($pref);
        foreach ($arr as $k => $v) {
            if (strncmp($k, $pref, $len)) {
                unset($arr[$k]);
            }
        }
        return $arr;
    }

    // -------------------------------------------------------------------------
    /**
     * Repeat the values of $array $times times.
     *
     * @param  array  $array
     * @param  int    $times Any non-negative integer
     * @return array  An indexed array containing the elements of $array $times times.
     */
    public static function repeat(array $array, int $times) {
        if ($times < 1) {
            return [];
        }

        $array = array_values($array);
        $ret = $array;
        while (--$times) {
            $ret = array_merge($ret, $array);
        }

        return $ret;
    }

    // -------------------------------------------------------------------------
    /**
     * Like array_slice(), only cyclic, as if the array was a ring and
     * we can slice from any point any number of items, sequentially.
     *
     * @param  array        $arr           A source array to slice from
     * @param  int          $offset        The position in the array to start from. Can be any integer.
     * @param  int|null     $length        If it is omitted or its absolute value is equal to count($arr),
     *                                     then the sequence will be a shifted version of the $arr.
     *                                     If negative, the sequence is obtainer in reverse order.
     *                                     Can be created than the length of $arr.
     * @param  bool|boolean $preserve_keys If true, preserve the keys, but the returned sequence can't be longer than $arr.
     *                                     If false, no key is preserved, not even string keys.
     * @return array
     */
    public static function cyclic_slice(array $arr, int $offset, ?int $length = null, bool $preserve_keys = false) {
        if ($length === 0) {
            return [];
        }

        $count = count($arr);
        if (!$count) {
            return $arr;
        }

        $length = $length ?? $count;

        $offset %= $count;
        if ($offset < 0) {
            $offset += $count;
        }
        if ($length < 0) {
            $length = -$length;
            $offset = $count - 1 - $offset;
            $arr = array_reverse($arr, $preserve_keys);
        }

        if (!$preserve_keys) {
            // Make sure $arr has no string keys, otherwhise array_slice() would preserve them.
            $arr = array_values($arr);
        }

        $ret = array_slice($arr, $offset, $length, $preserve_keys);

        $length += $offset - $count;
        if ($length > 0) {
            if ($length > $count) {
                if (!$preserve_keys) {
                    $ret = array_merge($ret, self::repeat($arr, $length / $count));
                }
                $length %= $count;
            }
            if ($preserve_keys) {
                $ret += array_slice($arr, 0, $length, $preserve_keys);
            }
            else {
                $ret = array_merge($ret, array_slice($arr, 0, $length, $preserve_keys));
            }
        }

        return $ret;
    }

    // -------------------------------------------------------------------------
    public static function part_unique(array $_pw, $u = true) {
        if ($u === true || $u === 1) {
            return array_unique($_pw);
        }
        $ww =
            $_pwu = [];
        if ($u < 0) {
            foreach ($_pw as $t) {
                for ($i = $u; $i < 0; $i++) {
                    $p = substr($t, 0, $i);
                    unset($_pwu[$ww[$p]]);
                    $ww[$p] = $t;
                }
                $_pwu[$t] = $t;
            }
        }
        elseif ($u < 1) {
            foreach ($_pw as $t) {
                for ($l = strlen($t), $i = round(strlen($t) * $u); $i < $l; $i++) {
                    $p = substr($t, 0, $i);
                    unset($_pwu[$ww[$p]]);
                    $ww[$p] = $t;
                }
                $_pwu[$t] = $t;
            }
        }
        return $_pwu;
    }

    // -------------------------------------------------------------------------

    public static function shuffle_assoc(array &$array) {
        $keys = array_keys($array);

        if (!shuffle($keys)) {
            return false;
        }
        $new = [];

        foreach ($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;

        return true;
    }

    // -------------------------------------------------------------------------
    /**
     * Given a list of [ child => parent ] items,
     * remove keys whose values doesn't exist in $list, recursively.
     *
     * $mp = array_diff($list, array_keys($list))
     * $ep = array_intersect($list, array_keys($list))
     * It is like running `$list = array_intersect($list, array_keys($list))` a lot of times
     *
     * @param  array $list of [ child => parent ] IDs
     * @param  integer|string $root key to preserve
     * @return array filtered
     */
    public static function remove_orphan_keys(array $list, $root = null) {
        if (isset($root)) {
            is_array($root) or $root = [$root];
        }
        else {
            $root = array_keys($list, null, true);
            // $root[] = NULL;
        }
        $root = array_combine($root, $root);

        $i = 0;
        do {
            ++$i;
            $c = count($list);
            $list = array_diff($list, array_diff($list, array_keys($list), $root)) +
                array_intersect_key($list, $root);
        }
        while ($c && count($list) < $c);

        return $list;
    }

    // public static function remove_orphan_keys_1($list, $root = 1) {
    //     $root_parent = $list[$root];
    //     foreach($list as $child => $parent) {
    //         if ( $parent && $parent != $root && $child != $root ) {
    //             $c = $child;
    //             $p = $parent;
    //             $id = [ $c => $c ];
    //             while( $p && $p != $root ) {
    //                 $p = $list[$c = $p];
    //                 if($p) {
    //                     $id[$c] = $c;
    //                 }
    //             }
    //             if($c && !$p && $c != $root) {
    //                 foreach($id as $i) unset($list[$i]);
    //             }
    //         }
    //     }
    //     return $list;
    // }

    // -------------------------------------------------------------------------

    /**
     * [7 => 'a', 5 => 'a', 3 => 'b'] !$idx -> ['a' => [ 0 => 7, 1 => 5 ], 'b' => 3 ]
     *                                 $idx -> ['a' => [ 7 => 7, 5 => 5 ], 'b' => 3 ]
     */
    public static function flip_grouped($arr, $idx = false) {
        $s = array_flip($arr);
        if (count($s) != count($arr)) {
            $arr = array_diff_key($arr, array_flip($s));
            $g = [];
            if ($idx) {
                foreach ($arr as $i => &$k) {
                    $g[$k][$i] = $i;
                }
                foreach ($g as $i => &$k) {
                    $k[$s[$i]] = $s[$i]; // array_flip() preserves last occurrences of key from $arr
                    $s[$i] = &$k;
                }
            }
            else {
                foreach ($arr as $i => &$k) {
                    $g[$k][] = $i;
                }
                foreach ($g as $i => &$k) {
                    $k[] = $s[$i]; // array_flip() preserves last occurrences of key from $arr
                    $s[$i] = &$k;
                }
            }
            unset($g);
        }
        return $s;
    }

    /**
     * Flip each <key> with value $arr[<key>][$field].
     *
     * @param (array)$arr    - multidimensional array
     * @param (scalar)$field - field name of second dimension of $arr
     * @param (bool)$group   - If a value has several occurrences and $group is false, the latest key will be used as its values, but if $group is true, then keys of same value will be grouped into an array
     *
     * @return (array) fliped array
     *
     * @author Dumitru Uzun (DUzun)
     *
     */
    public static function flip_by_field($arr, $field, $group = false) {
        $ret = [];
        foreach ($arr as $i => $v) {
            $n = $v[$field];
            if ($group && isset($ret[$n])) {
                is_array(@$ret[$n]) ? $ret[$n][] = $i : $ret[$n] = [$ret[$n], $i];
            }
            else {
                $ret[$n] = $i;
            }
        }
        return $ret;
    }

    /**
     * Transforms keys of an array applying the callback function to each key.
     *
     * @param (array)$arr         - subject array
     * @param (callback)$callback - a function which takes $arr key as first argument and returns new key
     * @param (bool)$group        - If two or more keys result in one new key after transformation and $group is false, the latest value will be used, but if $group is true, then all values of these keys will be grouped into an array
     * @param (mixed)$data        - any value to be passed as second argument to the $callback
     *
     * @return (array) resulting array
     *
     * @author Dumitru Uzun (DUzun)
     *
     */
    public static function transform_key($arr, $callback, $group = false, $params = null) {
        if (!is_callable($callback)) {
            throw new \Exception(__METHOD__ . ': ' . $callback . '() is not callable!');
        }

        $k = null;
        $ret = [];
        $params['k'] = &$k;
        if (array_key_exists('v', $params)) {
            $v = null;
            $params['v'] = &$v;
        }

        foreach ($arr as $k => $v) {
            $k = call_user_func_array($callback, $params);
            if ($k !== false) {
                if ($group && isset($ret[$k])) {
                    $v = array_merge((array) $ret[$k], (array) $v);
                }
                if (is_array($k)) {
                    $r = &$ret;
                    foreach ($k as $t) {
                        $r = &$r[$t];
                    }

                    $r = $v;
                    unset($r);
                }
                else {
                    $ret[$k] = $v;
                }
            }
        }
        return $ret;
    }

    /**
     * Transforms values and/or keys of an array applying the callback function to them.
     *
     * @param (array)$arr         - subject array
     * @param (callback)$callback - a function to be called on each keys and/or value of $arr, in order specified by $params
     *
     * @param (array)$params      - list of $callback parameters.
     *                                $params['k'] - iterates keys of $arr.
     *                                $params['v'] - iterates values of $arr.
     *
     *                                if 'k' key is present in $params, then keys of $arr will be transformed
     *                                     the return of $callback is the new key or false if to be omitted
     *                                     if initial $params['k'] is true then allow grouping of values on key collision at transformation
     *
     *                                if 'v' key is present in $params, then values of $arr will be transformed,
     *                                     the return of $callback is the new value
     *                                     if initial $params['v'] is true then omit NULL returns of $callback
     *
     *                                if 'k' and 'v' keys are present in $params, then both keys and values of $arr will be transformed
     *                                     the return of $callback is an array of new key/value pairs or empty if to be omitted
     *
     * @return (array) resulting array
     *
     * @author Dumitru Uzun (DUzun)
     *
     */
    public static function transform($arr, $callback, $params = null) {
        if (!is_callable($callback)) {
            throw new \Exception(__METHOD__ . ': ' . $callback . '() is not callable!');
        }

        $k = null;
        $v = null;
        $ret = [];
        $del_null = $group = null;
        $trel = 0;
        if (array_key_exists('k', $params)) {
            $group = (bool) $params['k'];
            $params['k'] = &$k;
            $trel |= 1;
        }
        if (!$trel || array_key_exists('v', $params)) {
            $del_null = (bool) $params['v'];
            $params['v'] = &$v;
            $trel |= 2;
        }

        switch ($trel) {
            case 1: foreach ($arr as $k => $v) {
                $k = call_user_func_array($callback, $params);
                if ($k !== false) {
                    if (is_array($k)) {
                        if (!$k) {
                            continue;
                        }

                        $p = &$ret;
                        foreach ($k as $t) {
                            $r = &$p;
                            $p = &$r[$t];
                        }
                        $k = $t;
                        unset($p);
                    }
                    else {
                        $r = &$ret;
                    }
                    if ($group && isset($r[$k])) {
                        $r[$k] = array_merge((array) $r[$k], (array) $v);
                    }
                    else {
                        $r[$k] = $v;
                    }
                }
            }
                unset($r);
                break;

            case 2: foreach ($arr as $k => $v) {
                $v = call_user_func_array($callback, $params);
                $v === null and $del_null or $ret[$k] = $v;
            }
                break;

            case 3: foreach ($arr as $k => $v) {
                if ($t = call_user_func_array($callback, $params)) {
                    [$k, $v] = $t;
                    if ($v === null && $del_null) {
                        continue;
                    }

                    if (is_array($k)) {
                        if (!$k) {
                            continue;
                        }

                        $p = &$ret;
                        foreach ($k as $t) {
                            $r = &$p;
                            $p = &$r[$t];
                        }
                        $k = $t;
                        unset($p);
                    }
                    else {
                        $r = &$ret;
                    }
                    if ($group && isset($r[$k])) {
                        $r[$k] = array_merge((array) $r[$k], (array) $v);
                    }
                    else {
                        $r[$k] = $v;
                    }
                }
            }
                break;
        }
        unset($r, $v, $k);

        return $ret;
    }

    // -------------------------------------------------------------------------
    /**
     * Get a sample of a given size of the $arr.
     *
     * @param (array)$arr The source array
     * @param (int|float)$size If >= 1, the absolute size of the sample.
     *                         If < 1, the relative size of the sample.
     * @param (callable) $validate A function returning true/false or a scalar value.
     *                             When $validate returns false, ::sample() stops traversing $arr and returns false.
     *                             If $validate returns true for all values of $arr, ::sample() returns true.
     *                             If $validate returns other scalar values, ::sample()
     *                             returns an associative array with these values at keys
     *                             and the proportion of occurrences as values (sum($ret) == 1.0).
     * @return (array|bool)
     */
    public static function sample($arr, $size, ?callable $validate = null) {
        $len = count($arr);
        if ($size < 0) {
            $size = $len + $size % $len;
        }
        if ($size == 0) {
            return $validate ? true : [];
        }
        elseif ($size >= $len) {
            if (!$validate) {
                return $arr;
            }
            $size = $len;
            $pace = 1.0;
        }
        elseif ($size < 1) {
            $pace = 1 / $size;
            $size = (int)ceil($size * $len);
        }
        else {
            $pace = $len / ($size - ($size > 1));
        }

        $ret = [];
        $i = 0.0;
        if ($validate) {
            $count = 0;
            foreach ($arr as $k => $v) {
                if ($i < 1.0) {
                    $r = $validate($v, $k, $ret);
                    if ($r === false) {
                        return false;
                    }
                    if ($r !== true) {
                        $ret[$r] = ($ret[$r] ?? 0) + 1;
                    }
                    if ($count++ >= $size) {
                        break;
                    }
                    $i += $pace;
                }
                $i -= 1.0;
            }
            if ($count < $size) {
                $r = $validate($v, $k, $ret);
                if ($r === false) {
                    return false;
                }
                if ($r !== true) {
                    $ret[$r] = ($ret[$r] ?? 0) + 1;
                }
            }
            if (!$ret) {
                return true;
            }
            foreach ($ret as &$count) {
                $count /= $size;
            }
            unset($count); // break ref
            return $ret;
        }

        foreach ($arr as $k => $v) {
            if ($i < 1.0) {
                $ret[$k] = $v;
                if (count($ret) == $size) {
                    return $ret;
                }
                $i += $pace;
            }
            $i -= 1.0;
        }
        if ($i < 1.0) {
            $ret[$k] = $v;
        }
        return $ret;
    }

    // -------------------------------------------------------------------------
    /**
     * Sort an array by fields
     *
     * @param  array         &$arr     The input array
     * @param  array|string  $fields   Fields to sort by:
     *                                   array(f1, f2, f3, f4 => -1, f5 => 1)
     *                                   "f1,f2,f3,f4:-,f5:+"
     *
     * @param  integer $dir      1 - ASC, -1 - DESC
     * @param  boolean $str_sort
     * @param  boolean $big_null
     * @return array             Sorted array
     */
    public static function fasort(&$arr, $fields = null, $dir = 1, $str_sort = false, $big_null = false) {
        count($arr) > 1 || !is_array($arr) and
            uasort($arr, [new _fasort_cmp_class($fields, $dir, $str_sort ? ($big_null ? 't' : 's') : ($big_null ? 'l' : 'n')), '_']);
        return $arr;
    }

    // -------------------------------------------------------------------------
    // -------------------------------------------------------------------------

}

/**
 * Internal class, used by Array::fasort()
 *
 * @private
 */
class _fasort_cmp_class {
    /**
     * Fields to sort by
     * @var array|null
     */
    private $f;

    /**
     * Comparison method
     * @var string
     */
    private $c;

    public function __construct($fields, $dir, $cmp = 'n') {
        $dir = (int) $dir < 0 ? -1 : 0;
        $f = null;
        if (is_array($fields)) {
            foreach ($fields as $n => $v) {
                if (!is_int($n) && is_int($v)) {
                    $v = $v < 0 ? ~$dir : $dir;
                }
                else {
                    $n = $v;
                    $v = $dir;
                }
                $f[$n] = $v;
            }
        }
        else {
            $fields = preg_split('#[,;\\|]#', $fields);
            foreach ($fields as $v) {
                @[$n, $v] = preg_split('#[\\:=]#', $v);
                $v = isset($v) && (int) ($v . '1') < 0 ? ~$dir : $dir;
                $f[$n] = $v;
            }
        }

        if ($f) {
            foreach ($f as $n => $v) {
                if (substr($n, -1) == '(') {
                    $v = [$v];
                    $t = explode('(', substr($n, 0, -1));
                    foreach ($t as $i) {
                        if (!is_callable($i)) {
                            throw new \Exception($i . ' is not a valid function!');
                        }
                        $v[] = $i;
                    }
                    $f[$n] = $v;
                }
            }
        }

        $this->f = $f;
        $this->c = $cmp;
    }

    /**
     * Compare two arrays by fields.
     *
     * @param  array $a
     * @param  array $b
     * @return int   0 for ==, -1 for < and 1 for >
     */
    public function _($a, $b) {
        $c = $this->c;
        foreach ($this->f as $f => $d) {
            if (is_array($d)) {
                $i = count($d);
                $x = $a;
                $y = $b;
                while (--$i) {
                    $f = $d[$i];
                    $x = $f($x); // $x = call_user_func($f, $x);
                    $y = $f($y); // $y = call_user_func($f, $y);
                }
                $d = $d[0];
            }
            else {
                $x = is_object($a) ? $a->$f : @$a[$f];
                $y = is_object($b) ? $b->$f : @$b[$f];
            }
            if ($r = $this->$c($x, $y)) {
                return $r ^ $d;
            }
        }
        return 0;
    }

    /**
     * Comparison method for simple (scalar?) values
     * @param  mixed $x
     * @param  mixed $y
     * @return int
     */
    public function n($x, $y) {
        if ($x < $y) {
            return -2;
        }

        if ($x > $y) {
            return +1;
        }
        return 0;
    }

    public function l($x, $y) {
        if (isset($x) && isset($y)) {
            if ($x < $y) {
                return -2;
            }

            if ($x > $y) {
                return +1;
            }
        }
        else {
            if (isset($x)) {
                return -2;
            }

            if (isset($y)) {
                return +1;
            }
        }
    }

    public function s($x, $y) {
        if ($r = strcmp($x, $y)) {
            return $r & -2 | 2;
        }
    }

    public function t($x, $y) {
        if (isset($x) && isset($y)) {
            if ($r = strcmp($x, $y)) {
                return $r & -2 | 2;
            }
        }
        else {
            if (isset($x)) {
                return -2;
            }

            if (isset($y)) {
                return +1;
            }
        }
    }
}
