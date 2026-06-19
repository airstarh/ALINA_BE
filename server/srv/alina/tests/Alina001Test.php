<?php

/**
 * DO NOT FORGET ADD phpUnit to %PATH%!!!
 * cd F:\_REPO\ALINA\_backend\alina
 * phpunit unitTests/testFunctions.php
 *
 * vendor/bin/phpunit --version
 * vendor/bin/phpunit ./tests/Alina001Test.php
 */

require_once __DIR__ . '/../AppBoot.php';

use alina\mvc\Model\user;
use PHPUnit\Framework\TestCase;

final class Alina001Test extends TestCase
{
    public function testZero()
    {
        echo PHP_EOL . __FUNCTION__;

        $this->assertIsNumeric(time());
    }

    public function testDb()
    {
        echo PHP_EOL . __FUNCTION__;

        $user = new user();
        $user->getOne([]);

        $this->assertIsNumeric($user->id);
        $this->assertNotEmpty($user->id);
    }

    public function testMergeSimpleObjects()
    {
        echo PHP_EOL . __FUNCTION__;

        $this->assertInstanceOf(
            stdClass::class,
            alina\Utils\Data::mergeObjects([], [])
        );
    }
}
