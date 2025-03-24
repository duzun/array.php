<?php

namespace duzun\ArrayClass\Tests;

/**
 * Mock class for testing validator functions
 */
class ValidatorMock {
    public $calls = 0;

    public function reset() {
        $this->calls = 0;
    }

    public function falser() {
        return $this->wrap(function () {
            return false;
        });
    }

    public function truer() {
        return $this->wrap(function () {
            return true;
        });
    }

    public function is_inter() {
        return $this->wrap(function ($v) {
            return is_int($v);
        });
    }

    public function is_evener() {
        return $this->wrap(function ($v) {
            return ($v & 1) ? 'odd' : 'even';
        });
    }

    public function gettyper() {
        return $this->wrap(function ($v) {
            return gettype($v);
        });
    }

    public function wrap(callable $callable) {
        $this->reset();
        return function (...$args) use ($callable) {
            $this->calls++;
            return $callable(...$args);
        };
    }
}
