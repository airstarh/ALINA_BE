<?php

require_once __DIR__ . '/../AppBoot.php';

use alina\mvc\Controller\Vanya;
use PHPUnit\Framework\TestCase;

final class VanyaControllerTest extends TestCase
{
    public function testIndexOutputsCurrentTime(): void
    {
        $controller  = new Vanya();
        $currentTime = $controller->currentTime();

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $currentTime);
        $this->assertLessThanOrEqual(2, abs(time() - strtotime($currentTime)));
    }
}
