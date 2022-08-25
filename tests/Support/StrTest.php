<?php

use Winter\Storm\Support\Str;

class StrTest extends TestCase
{
    public function testJoin()
    {
        $this->assertSame('', Str::join([]));
        $this->assertSame('bob', Str::join(['bob']));
        $this->assertSame('bob and joe', Str::join(['bob', 'joe']));
        $this->assertSame('bob, joe, and sally', Str::join(['bob', 'joe', 'sally']));
        $this->assertSame('bob, joe and sally', Str::join(['bob', 'joe', 'sally'], ', ', ' and '));
        $this->assertSame('bob, joe, and sally', Str::join(['bob', 'joe', 'sally']));
        $this->assertSame('bob or joe', Str::join(['bob', 'joe'], ', ', ', or ', ' or '));
        $this->assertSame('bob; joe; or sally', Str::join(['bob', 'joe', 'sally'], '; ', '; or '));
    }
}
