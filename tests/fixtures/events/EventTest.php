<?php

class EventTest extends TestCase
{
    public function __construct()
    {
        parent::__construct("testDummy");
    }

    public function testDummy()
    {
        $this->assertTrue(true, true);
    }
}
