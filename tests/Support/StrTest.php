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

    public function testIndex()
    {
        $this->assertSame('winter_cms_1',   Str::index('winter_cms'));
        $this->assertSame('winter_cms_2',   Str::index('winter_cms_1'));
        $this->assertSame('winter_cms_43',  Str::index('winter_cms_42'));
        $this->assertSame('winter_cms 3',   Str::index('winter_cms 2', separator: ' '));
        $this->assertSame('winter_cms 6',   Str::index('winter_cms 2', separator: ' ', step: 4));
        $this->assertSame('winter_cms8',    Str::index('winter_cms4', separator: '', step: 4));
        $this->assertSame('winter_cms4_1',  Str::index('winter_cms4', step: 4));
        $this->assertSame('winter_cms-22',  Str::index('winter_cms', separator: '-', starting: 22));
        $this->assertSame('winter cms 1',   Str::index('winter cms', separator: ' '));
        $this->assertSame('winter cms 2',   Str::index('winter cms 1', separator: ' '));
    }

    public function testUnique()
    {
        $this->assertSame('winter_cms_4',  Str::unique('winter_cms', ['winter_cms_1', 'test_5', 'winter_cms_3']));
        $this->assertSame('winter_cms_98', Str::unique('winter_cms', ['winter_cms_97', 'test_5', 'winter_cms_3']));
        $this->assertSame('winter_cms 1',  Str::unique('winter_cms', ['winter_cms_1', 'test_5', 'winter_cms_3'], ' '));
        $this->assertSame('winter_cms_1',  Str::unique('winter_cms', ['test_5']));
    }
}
