<?php

use Assetic\Asset\FileAsset;
use Winter\Storm\Parse\Assetic\Filter\LessCompiler;

/**
 * End-to-end regression for the LessCompiler safe-import wiring. Each test writes
 * a real `.less` file to a tmp directory, runs `filterLoad()` on it, and checks
 * the compiled CSS for either the absence of a sensitive marker (attack tests)
 * or the presence of a legitimate marker (preserve-functionality tests).
 *
 * See GHSA-58fp-mcx6-7qf9.
 */
class LessCompilerTest extends \Winter\Storm\Tests\TestCase
{
    /** @var string */
    protected $tmpRoot;

    /** @var string */
    protected $tmpReal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot = sys_get_temp_dir() . '/storm-less-compiler-' . bin2hex(random_bytes(4));
        mkdir($this->tmpRoot . '/theme/assets/less', 0777, true);
        mkdir($this->tmpRoot . '/cross-tree', 0777, true);
        $this->tmpReal = realpath($this->tmpRoot);

        // Sensitive file that an attacker would try to disclose.
        file_put_contents($this->tmpReal . '/secret.env', "APP_KEY=do-not-leak-me\nDB_PASSWORD=hunter2\n");
    }

    protected function tearDown(): void
    {
        (new \Winter\Storm\Filesystem\Filesystem())->deleteDirectory($this->tmpRoot);
        parent::tearDown();
    }

    public function testBlocksAbsolutePathImportAttack()
    {
        $main = $this->tmpReal . '/theme/assets/less/main.less';
        file_put_contents($main, '@import (inline) "' . $this->tmpReal . '/secret.env"; .x { color: red; }');

        $css = $this->compile($main);

        $this->assertStringNotContainsString('APP_KEY', $css);
        $this->assertStringNotContainsString('do-not-leak-me', $css);
    }

    public function testBlocksRelativeTraversalImportAttack()
    {
        $main = $this->tmpReal . '/theme/assets/less/main.less';
        // From /theme/assets/less, traverse up to the tmp root.
        file_put_contents($main, '@import (inline) "../../../secret.env"; .x { color: red; }');

        $css = $this->compile($main);

        $this->assertStringNotContainsString('APP_KEY', $css);
        $this->assertStringNotContainsString('do-not-leak-me', $css);
    }

    public function testAllowsLegitimateSameTreePartial()
    {
        $main = $this->tmpReal . '/theme/assets/less/main.less';
        $partial = $this->tmpReal . '/theme/assets/less/partial.less';
        file_put_contents($partial, '.partial-marker { color: orange; }');
        file_put_contents($main, '@import "partial.less"; .main-marker { color: blue; }');

        $css = $this->compile($main);

        $this->assertStringContainsString('partial-marker', $css);
        $this->assertStringContainsString('main-marker', $css);
    }

    public function testAllowsCrossTreeImportWhenRootIsWhitelisted()
    {
        $main = $this->tmpReal . '/theme/assets/less/main.less';
        $cross = $this->tmpReal . '/cross-tree/cross.less';
        file_put_contents($cross, '.cross-tree-marker { color: green; }');
        file_put_contents($main, '@import "' . $cross . '"; .main { color: blue; }');

        $compiler = new LessCompiler();
        $compiler->setAllowedImportRoots([$this->tmpReal . '/cross-tree']);

        $css = $this->compile($main, $compiler);

        $this->assertStringContainsString('cross-tree-marker', $css);
    }

    public function testBlocksCrossTreeImportWhenRootIsNotWhitelisted()
    {
        $main = $this->tmpReal . '/theme/assets/less/main.less';
        $cross = $this->tmpReal . '/cross-tree/cross.less';
        file_put_contents($cross, '.cross-tree-marker { color: green; }');
        file_put_contents($main, '@import "' . $cross . '"; .main { color: blue; }');

        // No setAllowedImportRoots — cross-tree must be denied by default.
        $css = $this->compile($main);

        $this->assertStringNotContainsString('cross-tree-marker', $css);
    }

    protected function compile(string $sourceFile, ?LessCompiler $compiler = null): string
    {
        $compiler ??= new LessCompiler();
        $asset = new FileAsset($sourceFile, [], dirname($sourceFile), basename($sourceFile));
        $asset->load();
        $compiler->filterLoad($asset);
        return $asset->getContent();
    }

}
