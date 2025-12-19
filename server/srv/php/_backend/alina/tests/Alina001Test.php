<?php

/**
 * DO NOT FORGET ADD phpUnit to %PATH%!!!
 * cd F:\_REPO\ALINA\_backend\alina
 * phpunit unitTests/testFunctions.php
 * 
 * vendor/bin/phpunit --version
 * vendor/bin/phpunit ./tests/Alina001Test
 */

// require_once __DIR__ . '/../app.php';
require_once __DIR__ . '/../AppBoot.php';
//require_once  __DIR__.'/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

final class Alina001Test extends TestCase
{
    public function testMergeSimpleObjects()
    {
        $this->assertInstanceOf(
            \stdClass::class,
            \alina\Utils\Data::mergeObjects([], [])
        );
    }
}
