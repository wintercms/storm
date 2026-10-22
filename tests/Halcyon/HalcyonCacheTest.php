<?php

use Winter\Storm\Filesystem\Filesystem;
use Winter\Storm\Halcyon\Datasource\FileDatasource;
use Winter\Storm\Halcyon\Datasource\Resolver;
use Winter\Storm\Halcyon\MemoryCacheManager;
use Winter\Storm\Halcyon\Model;
use Winter\Storm\Tests\TestCase;

class HalcyonCacheTest extends TestCase
{
    protected $datasource;

    protected $targetFile;

    public function setUp(): void
    {
        parent::setUp();

        include_once __DIR__.'/../fixtures/halcyon/models/Page.php';

        $this->datasource = new class(__DIR__.'/../fixtures/halcyon/themes/theme1', new Filesystem) extends FileDatasource {
            public $selectOneCalls = 0;

            public function selectOne(string $dirName, string $fileName, string $extension): ?array
            {
                $this->selectOneCalls++;

                return parent::selectOne($dirName, $fileName, $extension);
            }
        };

        $resolver = new Resolver(['theme1' => $this->datasource]);
        $resolver->setDefaultDatasource('theme1');
        Model::setDatasourceResolver($resolver);
        Model::setCacheManager(new MemoryCacheManager($this->app));

        @unlink($this->targetFile = __DIR__.'/../fixtures/halcyon/themes/theme1/pages/cachetest.htm');
    }

    public function tearDown(): void
    {
        @unlink($this->targetFile);
        Model::unsetCacheManager();

        parent::tearDown();
    }

    public function testMissingTemplateIsServedFromCache()
    {
        $this->assertNull(HalcyonTestPage::remember(10)->find('cachetest'));
        $this->assertNull(HalcyonTestPage::remember(10)->find('cachetest'));

        // A new request starts with an empty memory cache and reads the external cache store
        Model::getCacheManager()->driver()->flushInternalCache();
        $this->assertNull(HalcyonTestPage::remember(10)->find('cachetest'));

        $this->assertEquals(1, $this->datasource->selectOneCalls);
    }

    public function testCachedMissIsBustedWhenTemplateIsCreated()
    {
        $this->assertNull(HalcyonTestPage::remember(10)->find('cachetest'));

        file_put_contents($this->targetFile, "title = \"Created\"\n==\n<p>Created</p>");

        $page = HalcyonTestPage::remember(10)->find('cachetest');
        $this->assertNotNull($page);
        $this->assertEquals('Created', $page->title);
        $this->assertEquals(2, $this->datasource->selectOneCalls);
    }

    public function testExistingTemplateIsServedFromCache()
    {
        $this->assertEquals('hello', HalcyonTestPage::remember(10)->find('home')->title);
        $this->assertEquals('hello', HalcyonTestPage::remember(10)->find('home')->title);

        $this->assertEquals(1, $this->datasource->selectOneCalls);
    }
}
