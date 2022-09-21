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

    public function testUnique()
    {
        // Original returned unmodified when already unique
        $this->assertSame('winter_cms', Str::unique('winter_cms', []));
        $this->assertSame('winter_cms', Str::unique('winter_cms', ['winter_cms_1', 'winter_cms_2']));

        // // String modified to be the default step higher than the highest index identified
        $this->assertSame('winter_cms_1', Str::unique('winter_cms', ['winter_cms']));
        $this->assertSame('winter_cms_4', Str::unique('winter_cms', ['winter_cms', 'winter_cms_1', 'test_5', 'winter_cms_3']));

        // String modified to be the default step higher than the highest index identified with reversed order of items
        $this->assertSame('winter_cms_98', Str::unique('winter_cms', ['winter_cms', 'winter_cms_97', 'test_5', 'winter_cms_3']));

        // String modified to be the provided step higher than the highest index identified with the provided separator
        $this->assertSame('winter_cms 5', Str::unique('winter_cms', ['winter_cms', 'winter_cms 1', 'test_5', 'winter_cms 3'], ' ', 2));
    }
}
