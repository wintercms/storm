<?php

namespace Winter\Storm\Tests\Database;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Winter\Storm\Database\MemoryCache;
use Winter\Storm\Tests\Database\Fixtures\Author;
use Winter\Storm\Tests\DbTestCase;

class MemoryCacheTest extends DbTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        MemoryCache::forgetInstance();
    }

    public function tearDown(): void
    {
        MemoryCache::forgetInstance();
        Carbon::setTestNow(null);

        parent::tearDown();
    }

    public function testDuplicateModelQueryWithLimitedColumns()
    {
        Author::create([
            'name' => 'Ben Thomson',
            'email' => 'hello@wintercms.com',
        ]);

        DB::enableQueryLog();

        $authorName = Author::orderBy('id')->value('name');
        $author = Author::orderBy('id')->first();

        // Should be two queries - one for the value and one for the full model
        // as they use different column selections
        $this->assertCount(2, DB::getQueryLog());

        $this->assertEquals('Ben Thomson', $authorName);
        $this->assertEquals('Ben Thomson', $author->name);
        $this->assertEquals('hello@wintercms.com', $author->email);

        // Re-run similar queries to test cache
        $authorEmail = Author::orderBy('id')->value('email');
        $authorName = Author::orderBy('id')->value('name');
        $author = Author::orderBy('id')->first();

        // The first query should not be cached as it's a different column,
        // the second and third queries should still be cached.
        $this->assertCount(3, DB::getQueryLog());

        $this->assertEquals('hello@wintercms.com', $authorEmail);
        $this->assertEquals('Ben Thomson', $authorName);
        $this->assertEquals('Ben Thomson', $author->name);
        $this->assertEquals('hello@wintercms.com', $author->email);
    }

    public function testRememberedQueryWithLimitedColumns()
    {
        Author::create([
            'name' => 'Ben Thomson',
            'email' => 'hello@wintercms.com',
        ]);

        // Disable the memory cache so that only the remember() cache is in play
        MemoryCache::instance()->enabled(false);

        Carbon::setTestNow(now());

        DB::enableQueryLog();

        $authorName = Author::orderBy('id')->remember(10)->value('name');
        $author = Author::orderBy('id')->remember(10)->first();

        // Should be two queries - one for the value and one for the full model
        // as they use different column selections
        $this->assertCount(2, DB::getQueryLog());

        $this->assertEquals('Ben Thomson', $authorName);
        $this->assertEquals('Ben Thomson', $author->name);
        $this->assertEquals('hello@wintercms.com', $author->email);

        // Re-run similar queries to test cache
        $authorEmail = Author::orderBy('id')->remember(10)->value('email');
        $authorName = Author::orderBy('id')->remember(10)->value('name');
        $author = Author::orderBy('id')->remember(10)->first();

        // The first query should not be cached as it's a different column,
        // the second and third queries should still be cached.
        $this->assertCount(3, DB::getQueryLog());

        $this->assertEquals('hello@wintercms.com', $authorEmail);
        $this->assertEquals('Ben Thomson', $authorName);
        $this->assertEquals('Ben Thomson', $author->name);
        $this->assertEquals('hello@wintercms.com', $author->email);

        // Just before expiry, the queries should still be cached
        Carbon::setTestNow(now()->addMinutes(9));

        $authorName = Author::orderBy('id')->remember(10)->value('name');
        $author = Author::orderBy('id')->remember(10)->first();

        $this->assertCount(3, DB::getQueryLog());

        // Once expired, the queries should hit the database again
        Carbon::setTestNow(now()->addMinutes(2));

        $authorName = Author::orderBy('id')->remember(10)->value('name');
        $author = Author::orderBy('id')->remember(10)->first();

        $this->assertCount(5, DB::getQueryLog());

        $this->assertEquals('Ben Thomson', $authorName);
        $this->assertEquals('Ben Thomson', $author->name);
        $this->assertEquals('hello@wintercms.com', $author->email);
    }

    public function testQueriesAreNotCachedWhenMemoryCacheIsDisabled()
    {
        Author::create([
            'name' => 'Ben Thomson',
            'email' => 'hello@wintercms.com',
        ]);

        MemoryCache::instance()->enabled(false);

        DB::enableQueryLog();

        $authorName = Author::orderBy('id')->value('name');
        $author = Author::orderBy('id')->first();

        // Should be two queries - one for the value and one for the full model
        // as they use different column selections
        $this->assertCount(2, DB::getQueryLog());

        $this->assertEquals('Ben Thomson', $authorName);
        $this->assertEquals('Ben Thomson', $author->name);
        $this->assertEquals('hello@wintercms.com', $author->email);

        // Re-run similar queries to test cache
        $authorEmail = Author::orderBy('id')->value('email');
        $authorName = Author::orderBy('id')->value('name');
        $author = Author::orderBy('id')->first();

        // None of the queries should be cached, so all three should hit the database
        $this->assertCount(5, DB::getQueryLog());

        $this->assertEquals('hello@wintercms.com', $authorEmail);
        $this->assertEquals('Ben Thomson', $authorName);
        $this->assertEquals('Ben Thomson', $author->name);
        $this->assertEquals('hello@wintercms.com', $author->email);
    }
}
