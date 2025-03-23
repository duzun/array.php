<?php
namespace duzun\ArrayClass\Tests;

/**
 * Mock class for testing validator functions
 */
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
