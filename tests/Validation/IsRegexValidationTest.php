<?php

use Illuminate\Filesystem\Filesystem;
use Winter\Storm\Translation\FileLoader;
use Winter\Storm\Translation\Translator;
use Winter\Storm\Validation\Factory;

class IsRegexValidationTest extends TestCase
{
    /**
     * @var Factory
     */
    protected $validation;

    /**
     * @var Translator
     */
    protected $translator;

    public function setUp(): void
    {
        parent::setUp();

        $path       = __DIR__ . '/../fixtures/lang';
        $fileLoader = new FileLoader(new Filesystem(), $path);
        $translator = new Translator($fileLoader, 'en');
        $this->translator = $translator;

        $this->validation = new Factory($this->translator, null);
    }

    // This validation should fail, as per Laravel pre-5.8, as well as current expected Winter functionality.
    public function testIsRegexRule()
    {
        $validator = $this->validation->make([
            'test_regex' => '/this is not a valid regex (.+)?#',
        ], [
            'test_regex' => 'is_regex'
        ]);

        $this->assertTrue($validator->fails());

        $validator = $this->validation->make([
            'test_regex' => '#this is a valid regex (.+)?#',
        ], [
            'test_regex' => 'is_regex'
        ]);

        $this->assertFalse($validator->fails());
    }
}
