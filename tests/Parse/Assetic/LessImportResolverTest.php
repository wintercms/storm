<?php

use Winter\Storm\Parse\Assetic\Filter\LessImportResolver;

/**
 * Regression coverage for the `@import` file-disclosure class of bug fixed in
 * response to GHSA-58fp-mcx6-7qf9. The resolver is the structural backstop that
 * prevents `wikimedia/less.php` from inlining arbitrary server-readable files.
 */
class LessImportResolverTest extends TestCase
{
    /** @var string */
    protected $tmpRoot;

    /** @var string Realpath form of {@see $tmpRoot} */
    protected $tmpReal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot = sys_get_temp_dir() . '/storm-less-resolver-' . bin2hex(random_bytes(4));
        mkdir($this->tmpRoot . '/inside/subdir', 0777, true);
        mkdir($this->tmpRoot . '/sibling', 0777, true);
        file_put_contents($this->tmpRoot . '/inside/partial.less', '.partial {}');
        file_put_contents($this->tmpRoot . '/inside/subdir/nested.less', '.nested {}');
        file_put_contents($this->tmpRoot . '/sibling/sibling.less', '.sibling {}');
        file_put_contents($this->tmpRoot . '/outside-secret.env', 'APP_KEY=leak');

        $this->tmpReal = realpath($this->tmpRoot);
    }

    protected function tearDown(): void
    {
        (new \Winter\Storm\Filesystem\Filesystem())->deleteDirectory($this->tmpRoot);
        parent::tearDown();
    }

    public function testAllowsRelativeImportWithinContextDir()
    {
        $resolver = LessImportResolver::makeResolver([], $this->tmpReal . '/inside');

        $result = $resolver('partial.less');

        $this->assertIsArray($result);
        $this->assertSame($this->tmpReal . '/inside/partial.less', $result[0]);
    }

    /**
     * less.php's `Less_Tree_Import::skip()` calls `getFilePath()` with the raw
     * pre-extension-append filename (e.g. "partial" rather than "partial.less")
     * to build the import-once dedup key. The resolver must mirror less.php's
     * own `.less` extension fallback or every legitimate import poisons the
     * shared sentinel into the onceMap and subsequent imports get skipped.
     * This was a regression caught when the demo theme stopped compiling.
     */
    public function testAllowsRelativeImportMissingDotLessExtension()
    {
        $resolver = LessImportResolver::makeResolver([], $this->tmpReal . '/inside');

        $result = $resolver('partial');

        $this->assertSame($this->tmpReal . '/inside/partial.less', $result[0]);
    }

    public function testAllowsRelativeImportIntoSubdirectoryOfContextDir()
    {
        $resolver = LessImportResolver::makeResolver([], $this->tmpReal . '/inside');

        $result = $resolver('subdir/nested.less');

        $this->assertSame($this->tmpReal . '/inside/subdir/nested.less', $result[0]);
    }

    public function testBlocksRelativeTraversalEscapeOutsideContextDir()
    {
        $resolver = LessImportResolver::makeResolver([], $this->tmpReal . '/inside');

        $result = $resolver('../sibling/sibling.less');

        $this->assertSame(LessImportResolver::SENTINEL_PATH, $result[0]);
    }

    public function testBlocksAbsolutePathOutsideAllowedRoots()
    {
        $resolver = LessImportResolver::makeResolver([], $this->tmpReal . '/inside');

        $result = $resolver($this->tmpReal . '/outside-secret.env');

        $this->assertSame(LessImportResolver::SENTINEL_PATH, $result[0]);
    }

    public function testAllowsAbsolutePathInsideExplicitlyWhitelistedRoot()
    {
        $resolver = LessImportResolver::makeResolver([$this->tmpReal . '/sibling'], $this->tmpReal . '/inside');

        $result = $resolver($this->tmpReal . '/sibling/sibling.less');

        $this->assertSame($this->tmpReal . '/sibling/sibling.less', $result[0]);
    }

    public function testDenyAllWhenContextDirIsNullAndRootsEmpty()
    {
        $resolver = LessImportResolver::makeResolver([], null);

        $this->assertSame(LessImportResolver::SENTINEL_PATH, $resolver('partial.less')[0]);
        $this->assertSame(LessImportResolver::SENTINEL_PATH, $resolver('/etc/passwd')[0]);
        $this->assertSame(LessImportResolver::SENTINEL_PATH, $resolver('../whatever')[0]);
    }

    public function testReturnsSentinelForUnresolvableFilenames()
    {
        $resolver = LessImportResolver::makeResolver([], $this->tmpReal . '/inside');

        $this->assertSame(LessImportResolver::SENTINEL_PATH, $resolver('does-not-exist.less')[0]);
        $this->assertSame(LessImportResolver::SENTINEL_PATH, $resolver('')[0]);
    }

    public function testPrefixCollisionAttackBlocked()
    {
        // Create a root /base/.env (legit) and a sibling /base/.env_secret (must not match).
        $base = $this->tmpReal . '/inside';
        mkdir($base . '/.env', 0777, true);
        file_put_contents($base . '/.env_secret', 'must-not-leak');

        $resolver = LessImportResolver::makeResolver([$base . '/.env'], null);

        // Absolute path probe against the sibling — must not be accepted by the prefix check.
        $result = $resolver($base . '/.env_secret');

        $this->assertSame(LessImportResolver::SENTINEL_PATH, $result[0]);
    }

    public function testBuildImportDirsKeyMatchesLessPhpNormalisation()
    {
        $sourceFile = $this->tmpReal . '/inside/main.less';
        file_put_contents($sourceFile, '');

        $dirs = LessImportResolver::buildImportDirs($sourceFile, []);

        $this->assertCount(1, $dirs);
        $key = array_keys($dirs)[0];

        // less.php's SetFileInfo computes dirname(realpath($file)) and SetImportDirs
        // appends '/'. We must match exactly so PHP array_merge collides the auto-added
        // currentDirectory entry with our callable.
        $this->assertSame($this->tmpReal . '/inside/', $key);
        $this->assertInstanceOf(\Closure::class, $dirs[$key]);
    }

    public function testBuildImportDirsCallableAllowsSameDirImports()
    {
        $sourceFile = $this->tmpReal . '/inside/main.less';
        file_put_contents($sourceFile, '');

        $dirs = LessImportResolver::buildImportDirs($sourceFile, []);
        $callable = reset($dirs);

        $this->assertSame($this->tmpReal . '/inside/partial.less', $callable('partial.less')[0]);
        // Cross-tree (sibling) must be denied without an explicit allowed root.
        $this->assertSame(LessImportResolver::SENTINEL_PATH, $callable('../sibling/sibling.less')[0]);
    }

}
