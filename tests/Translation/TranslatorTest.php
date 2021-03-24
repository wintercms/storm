<?php

use Illuminate\Filesystem\Filesystem;
use Winter\Storm\Events\Dispatcher;
use Winter\Storm\Translation\FileLoader;
use Winter\Storm\Translation\Translator;

class TranslatorTest extends TestCase
{
    /**
     * @var Translator
     */
    private $translator;

    public function setUp(): void
    {
        parent::setUp();

        $path       = __DIR__ . '/../fixtures/lang';
        $fileLoader = new FileLoader(new Filesystem(), $path);
        $translator = new Translator($fileLoader, 'en');
        $this->translator = $translator;
    }

    public function testSimilarWordsParsing()
    {
        $this->assertEquals(
            'Displayed records: 1-100 of 10',
            $this->translator->get('lang.test.pagination', ['from' => 1, 'to' => 100, 'total' => 10])
        );
    }

    public function testChoice()
    {
        $this->assertEquals(
            'Page',
            $this->translator->choice('lang.test.choice', 1)
        );
        $this->assertEquals(
            'Pages',
            $this->translator->choice('lang.test.choice', 2)
        );
    }

    /**
     * Test case for https://github.com/octobercms/october/issues/4858
     *
     * @return void
     */
    public function testChoiceSublocale()
    {
        $this->translator->setLocale('en-au');

        $this->assertEquals(
            'Page',
            $this->translator->choice('lang.test.choice', 1)
        );
        $this->assertEquals(
            'Pages',
            $this->translator->choice('lang.test.choice', 2)
        );
    }

    public function testOverrideWithBeforeResolveEvent()
    {
        $eventsDispatcher = new Dispatcher();
        $this->translator->setEventDispatcher($eventsDispatcher);

        $this->assertEquals('Hello Winter!', $this->translator->get('lang.test.hello_winter'));

        $eventsDispatcher->listen('translator.beforeResolve', function () {
            return 'Hello Override!';
        });

        $this->assertEquals('Hello Override!', $this->translator->get('lang.test.hello_override'));
    }

    public function testOverrideWithAfterResolveEvent()
    {
        $eventsDispatcher = new Dispatcher();
        $this->translator->setEventDispatcher($eventsDispatcher);

        $this->assertEquals('Hello Winter!', $this->translator->get('lang.test.hello_winter'));

        $eventsDispatcher->listen('translator.afterResolve', function ($key, $replace, $line) {
            return str_replace('Hello', 'Hi', $line);
        });

        $this->assertEquals('Hi Winter!', $this->translator->get('lang.test.hello_winter'));
    }
}
