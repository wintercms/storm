<?php

namespace Winter\Storm\Tests\Database\Attach;

use Symfony\Component\HttpFoundation\HeaderUtils;
use Winter\Storm\Database\Attach\File;
use Winter\Storm\Support\Facades\File as FileHelper;
use Winter\Storm\Support\Str;
use Winter\Storm\Tests\TestCase;

/**
 * Covers the headers File::output() puts on a raw file response.
 *
 * The content type recorded for an attachment is derived from the bytes of the file, so it
 * does not have to agree with the name the file was stored under. Content the browser would
 * treat as a document must therefore not be displayed in place, and the stored name must not
 * be able to alter the structure of the Content-Disposition header.
 */
class FileOutputTest extends TestCase
{
    protected string $workDir;

    public function setUp(): void
    {
        parent::setUp();

        $this->workDir = sys_get_temp_dir() . '/storm-file-output-' . Str::random();
        mkdir($this->workDir);
    }

    public function tearDown(): void
    {
        FileHelper::deleteDirectory($this->workDir);

        parent::tearDown();
    }

    protected function makeFile(string $contents, string $fileName): File
    {
        $path = $this->workDir . '/' . Str::random() . '.bin';
        file_put_contents($path, $contents);

        return (new File())->fromFile($path, $fileName);
    }

    protected function makePngFile(string $fileName = 'avatar.png'): File
    {
        return (new File())->fromFile(
            dirname(__DIR__, 2) . '/fixtures/attach/avatar.png',
            $fileName
        );
    }

    protected function disposition(File $file, string $requested = 'inline'): string
    {
        return $file->output($requested, true)->headers->get('Content-Disposition');
    }

    /** Asserts the disposition type and name without caring how the name is quoted. */
    protected function assertDisposition(string $type, string $name, string $header): void
    {
        $parts = HeaderUtils::split($header, ';=');

        $this->assertEquals([$type], $parts[0]);
        $this->assertEquals(['filename', $name], $parts[1]);
    }

    /**
     * An HTML body under an image name: the gate that accepted the upload looked at the
     * name, the response describes the bytes, and the two disagree.
     */
    public function testHtmlContentIsNotDisplayedInPlace(): void
    {
        $file = $this->makeFile('<html><body><p>document</p></body></html>', 'me.jpg');

        $this->assertEquals('text/html', $file->getContentType());
        $this->assertStringStartsWith('attachment;', $this->disposition($file));
    }

    /**
     * SVG is an image type, but the browser renders it as a document. putFile() only
     * sanitizes a file stored under an `.svg` name, so this content reaches the response
     * untouched and must not be displayed in place either.
     */
    public function testSvgContentIsNotDisplayedInPlace(): void
    {
        $file = $this->makeFile(
            '<svg xmlns="http://www.w3.org/2000/svg"><rect width="1" height="1"/></svg>',
            'me.jpg'
        );

        $this->assertEquals('image/svg+xml', $file->getContentType());
        $this->assertStringStartsWith('attachment;', $this->disposition($file));
    }

    public function testXmlContentIsNotDisplayedInPlace(): void
    {
        $file = $this->makeFile('<?xml version="1.0"?><root><a>1</a></root>', 'me.jpg');

        $this->assertEquals('text/xml', $file->getContentType());
        $this->assertStringStartsWith('attachment;', $this->disposition($file));
    }

    /** A recorded content type carrying parameters is matched on its type, not verbatim. */
    public function testAContentTypeWithParametersIsStillRecognised(): void
    {
        $file = $this->makePngFile('me.png');
        $file->content_type = 'Text/HTML; charset=utf-8';

        $this->assertStringStartsWith('attachment;', $this->disposition($file));
    }

    /** Raw file responses declare that their content type is not to be second-guessed. */
    public function testResponsesDeclareTheContentTypeIsAuthoritative(): void
    {
        $response = $this->makePngFile()->output('inline', true);

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    /** An image still displays in place -- this is what the backend previews rely on. */
    public function testImageContentIsStillDisplayedInPlace(): void
    {
        $file = $this->makePngFile();

        $this->assertEquals('image/png', $file->getContentType());
        $this->assertDisposition('inline', 'avatar.png', $this->disposition($file));
    }

    /** As does a PDF, which the browser renders in its own viewer. */
    public function testPdfContentIsStillDisplayedInPlace(): void
    {
        $file = $this->makeFile("%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer<<>>\n%%EOF\n", 'report.pdf');

        $this->assertEquals('application/pdf', $file->getContentType());
        $this->assertDisposition('inline', 'report.pdf', $this->disposition($file));
    }

    //
    // Thumbnails
    //

    /**
     * A thumbnail is re-encoded from the extension it is stored under, so the response has to
     * describe the thumbnail. GIF bytes accepted under a ".png" name come back out of the
     * resizer as a PNG, and the type recorded for the original no longer applies.
     */
    public function testAThumbnailDescribesItsOwnBytes(): void
    {
        $path = $this->workDir . '/mismatched.gif';
        imagegif(imagecreatetruecolor(32, 32), $path);
        $file = (new File())->fromFile($path, 'mismatched.png');

        $this->assertEquals('image/gif', $file->getContentType());

        $response = $file->outputThumb(20, 20, [], true);

        $this->assertEquals('image/png', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith("\x89PNG", $response->getContent());
        $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
    }

    /**
     * The extension a thumbnail is stored under is chosen by the caller -- in the backend, by
     * the request -- so the disposition gate applies to it as well.
     */
    public function testAThumbnailStoredUnderADocumentExtensionIsNotDisplayedInPlace(): void
    {
        $response = $this->makePngFile()->outputThumb(20, 20, ['extension' => 'svg'], true);

        $this->assertEquals('image/svg+xml', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('attachment;', $response->headers->get('Content-Disposition'));
    }

    /** A caller that asked for a download still gets one. */
    public function testRequestedAttachmentDispositionIsUnchanged(): void
    {
        $this->assertDisposition(
            'attachment',
            'avatar.png',
            $this->disposition($this->makePngFile(), 'attachment')
        );
    }

    /**
     * The stored name is data, not header syntax: a quote in it must not be able to append
     * a second `filename` parameter for the client to prefer.
     */
    public function testTheStoredNameCannotAddASecondFilenameParameter(): void
    {
        $name = 'a"; filename="invoice.exe';
        $parts = HeaderUtils::split($this->disposition($this->makePngFile($name)), ';=');

        $this->assertEquals([['inline'], ['filename', $name]], $parts);
    }

    /**
     * A non-ASCII name is carried by `filename*` with an ASCII fallback, rather than being
     * emitted raw or rejected.
     */
    public function testNonAsciiNamesAreEncodedRatherThanRejected(): void
    {
        $disposition = $this->disposition($this->makePngFile('Rapport été.png'));

        $this->assertEquals(
            [['inline'], ['filename', 'Rapport ete.png'], ['filename*', "utf-8''Rapport%20%C3%A9t%C3%A9.png"]],
            HeaderUtils::split($disposition, ';=')
        );
    }

    /**
     * `%` is legal in a file name but not in the `filename` parameter, so it is replaced in
     * the fallback instead of failing the response.
     */
    public function testNamesContainingAPercentSignAreStillServed(): void
    {
        $disposition = $this->disposition($this->makePngFile('100% final.png'));

        $this->assertEquals(
            [['inline'], ['filename', '100_ final.png'], ['filename*', "utf-8''100%25%20final.png"]],
            HeaderUtils::split($disposition, ';=')
        );
    }
}
