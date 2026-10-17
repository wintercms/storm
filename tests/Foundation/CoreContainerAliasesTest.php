<?php

use Illuminate\Log\LogManager;

class CoreContainerAliasesTest extends \Winter\Storm\Tests\TestCase
{
    public function testLogManagerResolvesToTheLogService()
    {
        $this->assertSame($this->app->make('log'), $this->app->make(LogManager::class));
    }
}
