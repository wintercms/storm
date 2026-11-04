<?php

namespace Winter\Storm\Tests\Database\Attach;

use Exception;
use Winter\Storm\Database\Attach\File;
use Winter\Storm\Database\Attach\Resizer;
use Winter\Storm\Support\Facades\File as FileHelper;
use Winter\Storm\Support\Str;
use Winter\Storm\Tests\TestCase;

/**
 * Covers the extension a generated thumbnail is named with.
 *
 * `getThumbFilename()` interpolates the `extension` option into a name that is written to the
 * uploads disk, and callers take that option straight from request data --
 * `Backend\Controllers\Files::thumb()` reads it from a URL segment, reachable by any backend
 * user through the avatar attachment My Account gives everybody. Only the formats the resizer
 * can actually encode may appear there; otherwise the request picks the name of a file on disk.
 */
class FileThumbExtensionTest extends TestCase
{
    protected string $workDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->workDir = sys_get_temp_dir() . '/storm-thumb-extension-' . Str::random();
        mkdir($this->workDir);
    }

    public function tearDown(): void
    {
        FileHelper::deleteDirectory($this->workDir);

        parent::tearDown();
    }

    protected function makeFile(string $fileName = 'avatar.png'): File
    {
        $file = (new File())->fromFile(
            dirname(__DIR__, 2) . '/fixtures/attach/avatar.png',
            $fileName
        );

        $file->id = 7;

        return $file;
    }

    protected function thumbName(File $file, $extension): string
    {
        return $file->getThumbFilename(100, 100, ['extension' => $extension]);
    }

    //
    // Blocked: an extension the resizer cannot produce must not name the file
    //

    /**
     * The reported primitive. `php` is the one that matters, and the rest are the neighbouring
     * names a server might also execute or render.
     */
    public function testUnsupportedExtensionsCannotNameAThumbnail(): void
    {
        $file = $this->makeFile();

        foreach (['php', 'php5', 'phtml', 'phar', 'html', 'svg', 'sh'] as $extension) {
            $this->assertEquals(
                'thumb_7_100_100_0_0_auto.png',
                $this->thumbName($file, $extension),
                "'{$extension}' must not reach the thumbnail filename"
            );
        }
    }

    /** Casing is not a way around the allowlist, since the filesystem may not care about it. */
    public function testCasingCannotSmuggleAnUnsupportedExtension(): void
    {
        $file = $this->makeFile();

        foreach (['PHP', 'Php', 'pHp'] as $extension) {
            $this->assertEquals('thumb_7_100_100_0_0_auto.png', $this->thumbName($file, $extension));
        }
    }

    /**
     * `extension` defaults to `auto`, which copies the extension of the source file -- so an
     * attachment stored under a `.php` name reaches the same filename without the caller
     * naming an extension at all.
     */
    public function testAutoDoesNotCopyAnUnsupportedSourceExtension(): void
    {
        $file = $this->makeFile('payload.php');

        $this->assertEquals('php', $file->getExtension(), 'Precondition: the source is named .php');
        $this->assertEquals('thumb_7_100_100_0_0_auto.png', $this->thumbName($file, 'auto'));
        $this->assertEquals('thumb_7_100_100_0_0_auto.png', $file->getThumbFilename(100, 100));
    }

    //
    // Still works: every format the resizer can encode
    //

    public function testSupportedExtensionsAreKept(): void
    {
        $file = $this->makeFile();

        foreach (File::$imageExtensions as $extension) {
            $this->assertEquals(
                "thumb_7_100_100_0_0_auto.{$extension}",
                $this->thumbName($file, $extension),
                "'{$extension}' must still be usable"
            );
        }
    }

    /** `auto` keeps following the source file, which is what sizes the common case. */
    public function testAutoStillFollowsAnImageSourceExtension(): void
    {
        foreach (['avatar.png' => 'png', 'avatar.JPG' => 'jpg', 'avatar.webp' => 'webp'] as $name => $extension) {
            $this->assertEquals(
                "thumb_7_100_100_0_0_auto.{$extension}",
                $this->thumbName($this->makeFile($name), 'auto')
            );
        }
    }

    /** The rest of the name is untouched, so existing thumbnails keep their paths. */
    public function testTheRestOfTheFilenameIsUnchanged(): void
    {
        $file = $this->makeFile();

        $this->assertEquals(
            'thumb_7_640_480_0_0_crop.jpg',
            $file->getThumbFilename(640, 480, ['mode' => 'crop', 'extension' => 'jpg'])
        );
    }

    //
    // The allowlist and the encoder must not drift apart
    //

    /**
     * `$imageExtensions` is the allowlist above, so it has to stay exactly the set
     * `Resizer::save()` can encode. A format GD was built without still counts as supported --
     * it fails for a different reason, and its own `imagetypes()` guard handles that.
     */
    public function testTheAllowlistMatchesWhatTheResizerCanEncode(): void
    {
        $source = dirname(__DIR__, 2) . '/fixtures/attach/avatar.png';

        foreach (File::$imageExtensions as $extension) {
            $this->assertNull(
                $this->saveError($source, $extension),
                "Resizer::save() must be able to encode '{$extension}'"
            );
        }

        $this->assertStringContainsString(
            'Invalid image type',
            (string) $this->saveError($source, 'php'),
            'Precondition: the resizer itself rejects an extension outside the allowlist'
        );
    }

    /**
     * Returns the reason `Resizer::save()` refused the extension, or null if it did not refuse
     * it. "Support is not enabled" is a build detail rather than a refusal of the format.
     */
    protected function saveError(string $source, string $extension): ?string
    {
        try {
            Resizer::open($source)
                ->resize(10, 10, ['extension' => $extension])
                ->save($this->workDir . '/out.' . $extension);
        } catch (Exception $ex) {
            return Str::contains($ex->getMessage(), 'support is not enabled')
                ? null
                : $ex->getMessage();
        }

        return null;
    }
}
