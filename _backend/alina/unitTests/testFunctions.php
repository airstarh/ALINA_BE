<?php

/**
 * DO NOT FORGET ADD phpUnit to %PATH%!!!
 * cd F:\_REPO\ALINA\_backend\alina
 * phpunit unitTests/testFunctions.php
 */

require_once  __DIR__.'/../app.php';
require_once  __DIR__.'/../appBoot.php';
//require_once  __DIR__.'/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;

final class testFunctions extends TestCase
{
    public function testMergeSimpleObjects()
    {
        $this->assertInstanceOf(
            \stdClass::class,
            \alina\utils\Data::mergeObjects([],[])
        );
    }
}
