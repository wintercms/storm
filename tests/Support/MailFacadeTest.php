<?php

use Mockery as m;
use Winter\Storm\Mail\MailManager;
use Winter\Storm\Support\Facades\Mail;
use Winter\Storm\Support\Testing\Fakes\MailFake;

class MailFacadeTest extends \Winter\Storm\Tests\TestCase
{
    public function testFakeUsesTheApplicationMailManager()
    {
        $manager = $this->mockManager();
        $this->app->instance('mail.manager', $manager);

        $fake = Mail::fake();

        $this->assertInstanceOf(MailFake::class, $fake);
        $this->assertSame($manager, $fake->manager);
        $this->assertSame($fake, Mail::getFacadeRoot());
    }

    public function testFakeKeepsTheRealMailManagerWhenCalledAgain()
    {
        $manager = $this->mockManager();
        $this->app->instance('mail.manager', $manager);

        Mail::fake();
        $fake = Mail::fake();

        $this->assertSame($manager, $fake->manager);
    }

    public function testFakeAcceptsAMailManager()
    {
        $manager = $this->mockManager();

        $fake = Mail::fake($manager);

        $this->assertSame($manager, $fake->manager);
    }

    protected function mockManager(): MailManager
    {
        return m::mock(MailManager::class)->shouldReceive('getDefaultDriver')->andReturn('smtp')->getMock();
    }
}
