<?php
/**
 * You can install Rector globally with this command:
 *      composer global require rector/rector --dev
 */
declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\CodingStyle\Rector\FuncCall\ConsistentImplodeRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/ArrayClass.php',
        __DIR__ . '/tests/TestArrayClass.Test.php',
    ])
    // uncomment to reach your current PHP version
    // ->withPhpSets()
    ->withSets([
        SetList::PHP_71,
        SetList::PHP_72,
        SetList::PHP_73,
        SetList::PHP_74,
        SetList::PHP_80,
        SetList::PHP_81,
        SetList::PHP_82,
        SetList::PHP_83,
        SetList::PHP_84,
    ])
    ->withRules([
        ConsistentImplodeRector::class,
        ExplicitNullableParamTypeRector::class,
    ])
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
