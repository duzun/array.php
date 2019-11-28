<?php
// -----------------------------------------------------
/**
 *  @author Dumitru Uzun (DUzun.Me)
 */
// -----------------------------------------------------
define('PHPUNIT_DIR', dirname(__FILE__) . DIRECTORY_SEPARATOR);
define('ROOT_DIR', strtr(dirname(PHPUNIT_DIR), '\\', '/') . '/');

// -----------------------------------------------------
require_once ROOT_DIR . 'vendor/autoload.php';
// -----------------------------------------------------

// We have to make some adjustments for PHPUnit_BaseClass to work with
// PHPUnit 8.0 and still keep backward compatibility
if (!class_exists('PHPUnit_Runner_Version')) {
    class_alias('PHPUnit\Runner\Version', 'PHPUnit_Runner_Version');
}
if (version_compare(PHPUnit_Runner_Version::id(), '8.0.0') >= 0) {
    require_once PHPUNIT_DIR . '_PU8_TestCase.php';
} else {
    require_once PHPUNIT_DIR . '_PU7_TestCase.php';
}

// -----------------------------------------------------
// -----------------------------------------------------
/**
 * @backupGlobals disabled
 */
// -----------------------------------------------------
abstract class PHPUnit_BaseClass extends PU_TestCase
{
    public static $log = true;
    public static $testName;
    public static $className;
    // -----------------------------------------------------
    // Before every test
    public function mySetUp()
    {
        self::$testName = $this->getName();
        self::$className = get_class($this);

        // parent::mySetUp();
    }
    // -----------------------------------------------------
    /**
     * Asserts that a method exists.
     *
     * @param  string $methodName
     * @param  string|object  $className
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public static function assertMehodExists($methodName, $className, $message = '')
    {
        self::assertThat(method_exists($className, $methodName), self::isTrue(), $message);
    }

    // Alias to $this->assertMehodExists()
    public function assertClassHasMethod()
    {
        $args = func_get_args();
        return call_user_func_array(array($this, 'assertMehodExists'), $args);
    }

    // -----------------------------------------------------
    // Helper methods:

    public static function log()
    {
        if (empty(self::$log)) return;
        static $idx = 0;
        static $lastTest;
        static $lastClass;
        if ($lastTest != self::$testName || $lastClass != self::$className) {
            echo PHP_EOL, PHP_EOL, '-> ', self::$className . '::' . self::$testName, ' ()';
            $lastTest  = self::$testName;
            $lastClass = self::$className;
        }
        $args = func_get_args();
        foreach ($args as $k => $v) is_string($v) or is_int($v) or is_float($v) or $args[$k] = var_export($v, true);
        echo PHP_EOL,
            "",
            str_pad(++$idx, 3, ' ', STR_PAD_LEFT),
            ")\t",
            implode(' ', $args),
            PHP_EOL;
    }
    // -----------------------------------------------------
    public static function deleteTestData()
    { }
    // -----------------------------------------------------
    // -----------------------------------------------------
}
// -----------------------------------------------------
// Delete the temp test user after all tests have fired
register_shutdown_function('PHPUnit_BaseClass::deleteTestData');
// -----------------------------------------------------
