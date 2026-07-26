<?php

use Assetic\Asset\FileAsset;
use Winter\Storm\Parse\Assetic\Filter\JavascriptImporter;

/**
 * Regression coverage for the JavaScript combiner `=include`/`=require` file-disclosure
 * bug fixed in response to GHSA-2223-f22x-24cq. `JavascriptImporter` must not inline
 * arbitrary server-readable files reached via `..` traversal, and must reject non-`.js`
 * targets outright, while preserving legitimate same-tree and allowed-root includes.
 */
class JavascriptImporterTest extends TestCase
{
    /** @var string */
    protected $tmpRoot;

    /** @var string Realpath form of {@see $tmpRoot} */
    protected $tmpReal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot = sys_get_temp_dir() . '/storm-js-importer-' . bin2hex(random_bytes(4));
        mkdir($this->tmpRoot . '/assets/sub', 0777, true);
        mkdir($this->tmpRoot . '/allowed', 0777, true);

        // Secrets a level above the asset tree — the disclosure targets.
        file_put_contents($this->tmpRoot . '/secret.env', 'APP_KEY=leak-env');
        file_put_contents($this->tmpRoot . '/secret.js', 'var SECRET_JS = "leak-js";');

        // Legitimate includes.
        file_put_contents($this->tmpRoot . '/allowed/lib.js', 'var LIB = 1;');
        file_put_contents($this->tmpRoot . '/assets/partial.js', 'var PARTIAL = 1;');
        file_put_contents($this->tmpRoot . '/assets/sub/nested.js', 'var NESTED = 1;');

        $this->tmpReal = realpath($this->tmpRoot);
    }

    protected function tearDown(): void
    {
        (new \Winter\Storm\Filesystem\Filesystem())->deleteDirectory($this->tmpRoot);
        parent::tearDown();
    }

    /**
     * Run a JS entry file's contents through the importer and return the combined output.
     *
     * @param string[] $allowedRoots
     */
    protected function combine(string $entryContents, array $allowedRoots = []): string
    {
        $entry = $this->tmpReal . '/assets/entry.js';
        file_put_contents($entry, $entryContents);

        $filter = new JavascriptImporter();
        $filter->setAllowedImportRoots($allowedRoots);

        $asset = new FileAsset($entry, [$filter], $this->tmpReal . '/assets', 'entry.js');
        $asset->load();

        return $asset->dump();
    }

    public function testAllowsSameDirectoryInclude()
    {
        $out = $this->combine("/*\n=include partial.js\n*/\n");

        $this->assertStringContainsString('var PARTIAL = 1;', $out);
    }

    public function testAllowsSubdirectoryInclude()
    {
        $out = $this->combine("/*\n=include sub/nested.js\n*/\n");

        $this->assertStringContainsString('var NESTED = 1;', $out);
    }

    /**
     * Extension-less targets must continue to resolve with `.js` appended, so existing
     * assets that rely on the auto-append keep working after the hardening.
     */
    public function testExtensionlessIncludeStillResolvesToJs()
    {
        $out = $this->combine("/*\n=include partial\n*/\n");

        $this->assertStringContainsString('var PARTIAL = 1;', $out);
    }

    public function testBlocksEnvTraversal()
    {
        $out = $this->combine("/*\n=include ../secret.env\n*/\n");

        $this->assertStringNotContainsString('leak-env', $out);
        $this->assertStringContainsString('disallowed extension', $out);
    }

    public function testBlocksJsTraversalOutsideAllowedRoots()
    {
        $out = $this->combine("/*\n=include ../secret.js\n*/\n");

        $this->assertStringNotContainsString('leak-js', $out);
        $this->assertStringContainsString('outside the allowed import paths', $out);
    }

    public function testAllowsJsIncludeWithinExplicitlyAllowedRoot()
    {
        $out = $this->combine(
            "/*\n=include ../allowed/lib.js\n*/\n",
            [$this->tmpReal . '/allowed']
        );

        $this->assertStringContainsString('var LIB = 1;', $out);
    }

    public function testBlocksJsIncludeIntoRootNotWhitelisted()
    {
        // Same include as above, but without granting the `allowed` root.
        $out = $this->combine("/*\n=include ../allowed/lib.js\n*/\n");

        $this->assertStringNotContainsString('var LIB = 1;', $out);
        $this->assertStringContainsString('outside the allowed import paths', $out);
    }

    public function testRequireThrowsOnDisallowedExtension()
    {
        $this->expectException(\RuntimeException::class);

        $this->combine("/*\n=require ../secret.env\n*/\n");
    }
}
