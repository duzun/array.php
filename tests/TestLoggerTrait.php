<?php

namespace duzun\ArrayClass\Tests;

/**
 * Test Logger Trait - provides logging functionality for test classes
 *
 * @author Dumitru Uzun (DUzun.Me)
 */
trait TestLoggerTrait {
    /** @var bool */
    // public static $log = true; // define in the class

    /** @var string|null */
    protected static $testName;

    /** @var string|null */
    protected static $className;

    /**
     * Initialize test logging context
     */
    protected function initTestLogger() {
        self::$testName = method_exists($this, 'getName') ? $this->getName() : $this->name();
        self::$className = get_class($this);
    }

    /**
     * Log debug information during tests
     *
     * @param mixed ...$args Values to log
     */
    /**
     * @param mixed ...$args
     */
    public static function log(...$args) {
        if (!self::$log) {
            return;
        }

        static $idx = 0;
        static $lastTest;
        static $lastClass;

        if ($lastTest !== self::$testName || $lastClass !== self::$className) {
            echo PHP_EOL, PHP_EOL, '-> ', self::$className . '::' . self::$testName, ' ()';
            $lastTest = self::$testName;
            $lastClass = self::$className;
        }

        $formattedArgs = array_map(function ($v) {
            return is_string($v) || is_int($v) || is_float($v) ? $v : var_export($v, true);
        }, $args);

        echo PHP_EOL,
        str_pad(++$idx, 3, ' ', STR_PAD_LEFT),
        ")\t",
        implode(' ', $formattedArgs),
        PHP_EOL;
    }

    /**
     * Enable logging
     */
    public static function enableLog() {
        self::$log = true;
    }

    /**
     * Disable logging
     */
    public static function disableLog() {
        self::$log = false;
    }
}
