<?php

use Illuminate\Contracts\Translation\Loader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Translation\MessageSelector;
use Mockery as m;
use Winter\Storm\Events\Dispatcher;
use Winter\Storm\Translation\FileLoader;
use Winter\Storm\Translation\Translator;

/**
 * Some tests were adapted from the Laravel 9.x test cases to ensure our functionality still
 * provides the base functionality.
 *
 * Credits: https://github.com/laravel
 */
class TranslatorTest extends TestCase
{
    /**
     * @var Translator
     */
    private $translator;

    public function setUp(): void
    {
        parent::setUp();

        $path = __DIR__ . '/../fixtures/lang';
        $fileLoader = new FileLoader(new Filesystem(), $path);
        $translator = new Translator($fileLoader, 'en');
        $translator->addNamespace('winter.test', $path);
        $this->translator = $translator;
    }

    protected function tearDown(): void
    {
        m::close();
    }

    protected function getLoader()
    {
        return m::mock(Loader::class);
    }

    public function testHasMethodReturnsFalseWhenReturnedTranslationIsNull()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'))->willReturn('foo');
        $this->assertFalse($t->has('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en', 'sp'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'))->willReturn('bar');
        $this->assertTrue($t->has('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'), false)->willReturn('bar');
        $this->assertTrue($t->hasForLocale('foo', 'bar'));

        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo([]), $this->equalTo('bar'), false)->willReturn('foo');
        $this->assertFalse($t->hasForLocale('foo', 'bar'));

        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['foo' => 'bar']);
        $this->assertTrue($t->hasForLocale('foo'));

        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn([]);
        $this->assertFalse($t->hasForLocale('foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo', 'qux' => ['tree :foo', 'breeze :foo']]);
        $this->assertEquals(['tree bar', 'breeze bar'], $t->get('foo::bar.qux', ['foo' => 'bar'], 'en'));
        $this->assertSame('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesArrayItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo', 'qux' => ['tree :foo', 'breeze :foo', 'beep' => ['rock' => 'tree :foo']]]);
        $this->assertEquals(['foo' => 'foo', 'baz' => 'breeze bar', 'qux' => ['tree bar', 'breeze bar', 'beep' => ['rock' => 'tree bar']]], $t->get('foo::bar', ['foo' => 'bar'], 'en'));
        $this->assertSame('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodForNonExistingReturnsSameKey()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo', 'qux' => ['tree :foo', 'breeze :foo']]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'unknown', 'foo')->andReturn([]);
        $this->assertSame('foo::unknown', $t->get('foo::unknown', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo::bar.unknown', $t->get('foo::bar.unknown', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo::unknown.bar', $t->get('foo::unknown.bar'));
    }

    public function testTransMethodProperlyLoadsAndRetrievesItemWithHTMLInTheMessage()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'breeze <p>test</p>']);
        $this->assertSame('breeze <p>test</p>', $t->get('foo.bar', [], 'en'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemWithCapitalization()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods([])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :0 :Foo :BAR']);
        $this->assertSame('breeze john Bar FOO', $t->get('foo::bar.baz', ['john', 'foo' => 'bar', 'bar' => 'foo'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemWithLongestReplacementsFirst()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo :foobar']);
        $this->assertSame('breeze bar taylor', $t->get('foo::bar.baz', ['foo' => 'bar', 'foobar' => 'taylor'], 'en'));
        $this->assertSame('breeze foo bar baz taylor', $t->get('foo::bar.baz', ['foo' => 'foo bar baz', 'foobar' => 'taylor'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemForFallback()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->setFallback('lv');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'bar', 'foo')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('lv', 'bar', 'foo')->andReturn(['foo' => 'foo', 'baz' => 'breeze :foo']);
        $this->assertSame('breeze bar', $t->get('foo::bar.baz', ['foo' => 'bar'], 'en'));
        $this->assertSame('foo', $t->get('foo::bar.foo'));
    }

    public function testGetMethodProperlyLoadsAndRetrievesItemForGlobalNamespace()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'breeze :foo']);
        $this->assertSame('breeze bar', $t->get('foo.bar', ['foo' => 'bar']));
    }

    public function testSetMethodProperlySetsItem()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->set('foo', 'bar');
        $this->assertSame('bar', $t->get('foo'));
    }

    public function testSetMethodOverwritesPreviouslyLoadedItem()
    {
        $this->assertEquals('Hello Winter!', $this->translator->get('winter.test::lang.test.hello_winter'));

        $this->translator->set('winter.test::lang.test.hello_winter', 'Hi Winter!');

        $this->assertEquals('Hi Winter!', $this->translator->get('winter.test::lang.test.hello_winter'));
    }

    public function testSetMethodDoesNotPreventOtherLanguageStringsBeingLoadedNormally()
    {
        $path = __DIR__ . '/../fixtures/lang';
        $fileLoader = new FileLoader(new Filesystem(), $path);
        $translator = new Translator($fileLoader, 'en');

        // Set value first
        $translator->set('winter.test::lang.test.hello_winter', 'Hi Winter!');

        // Then, add the namespace
        $translator->addNamespace('winter.test', $path);

        $this->assertEquals('Hi Winter!', $translator->get('winter.test::lang.test.hello_winter'));

        // This should now be translated
        $this->assertEquals('Welcome to Winter!', $translator->get('winter.test::lang.test.welcome_to_winter'));
    }

    public function testSetMethodCanOverwriteAnEntireGroupForALocale()
    {
        $this->translator->set('winter.test::lang', [
            'test' => [
                'hello_winter' => 'Sup Winter?',
                'welcome_to_winter' => 'It\'s time for Winter!',
                'winter' => [
                    'simplicity' => 'Fully simple',
                    'stability' => 'Fully stable',
                ],
            ],
        ], 'en_BT');

        $this->translator->setLocale('en_BT');

        $this->assertEquals('Sup Winter?', $this->translator->get('winter.test::lang.test.hello_winter'));
        $this->assertEquals('It\'s time for Winter!', $this->translator->get('winter.test::lang.test.welcome_to_winter'));
        $this->assertEquals('Fully simple', $this->translator->get('winter.test::lang.test.winter.simplicity'));
        $this->assertEquals('Fully stable', $this->translator->get('winter.test::lang.test.winter.stability'));

        // Shouldn't be changed
        $this->assertEquals('Speed', $this->translator->get('winter.test::lang.test.winter.speed'));
        $this->assertEquals('Security', $this->translator->get('winter.test::lang.test.winter.security'));
    }

    public function testChoiceMethodProperlyLoadsAndRetrievesItem()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->once())->method('get')->with($this->equalTo('foo'), $this->equalTo(['replace']), $this->equalTo('en'))->willReturn('line');
        $t->setSelector($selector = m::mock(MessageSelector::class));
        $selector->shouldReceive('choose')->once()->with('line', 10, 'en')->andReturn('choiced');

        $t->choice('foo', 10, ['replace']);
    }

    public function testChoiceMethodProperlyCountsCollectionsAndLoadsAndRetrievesItem()
    {
        $t = $this->getMockBuilder(Translator::class)->onlyMethods(['get'])->setConstructorArgs([$this->getLoader(), 'en'])->getMock();
        $t->expects($this->exactly(2))->method('get')->with($this->equalTo('foo'), $this->equalTo(['replace']), $this->equalTo('en'))->willReturn('line');
        $t->setSelector($selector = m::mock(MessageSelector::class));
        $selector->shouldReceive('choose')->twice()->with('line', 3, 'en')->andReturn('choiced');

        $values = ['foo', 'bar', 'baz'];
        $t->choice('foo', $values, ['replace']);

        $values = new Collection(['foo', 'bar', 'baz']);
        $t->choice('foo', $values, ['replace']);
    }

    public function testGetJson()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['foo' => 'one']);
        $this->assertSame('one', $t->get('foo'));
    }

    public function testGetJsonReplaces()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['foo :i:c :u' => 'bar :i:c :u']);
        $this->assertSame('bar onetwo three', $t->get('foo :i:c :u', ['i' => 'one', 'c' => 'two', 'u' => 'three']));
    }

    public function testGetJsonHasAtomicReplacements()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['Hello :foo!' => 'Hello :foo!']);
        $this->assertSame('Hello baz:bar!', $t->get('Hello :foo!', ['foo' => 'baz:bar', 'bar' => 'abcdef']));
    }

    public function testGetJsonReplacesForAssociativeInput()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['foo :i :c' => 'bar :i :c']);
        $this->assertSame('bar eye see', $t->get('foo :i :c', ['i' => 'eye', 'c' => 'see']));
    }

    public function testGetJsonPreservesOrder()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn(['to :name I give :greeting' => ':greeting :name']);
        $this->assertSame('Greetings David', $t->get('to :name I give :greeting', ['name' => 'David', 'greeting' => 'Greetings']));
    }

    public function testGetJsonForNonExistingJsonKeyLooksForRegularKeys()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'one']);
        $this->assertSame('one', $t->get('foo.bar'));
    }

    public function testGetJsonForNonExistingJsonKeyLooksForRegularKeysAndReplace()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn(['bar' => 'one :message']);
        $this->assertSame('one two', $t->get('foo.bar', ['message' => 'two']));
    }

    public function testGetJsonForNonExistingReturnsSameKey()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'Foo that bar', '*')->andReturn([]);
        $this->assertSame('Foo that bar', $t->get('Foo that bar'));
    }

    public function testGetJsonForNonExistingReturnsSameKeyAndReplaces()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo :message', '*')->andReturn([]);
        $this->assertSame('foo baz', $t->get('foo :message', ['message' => 'baz']));
    }

    public function testEmptyFallbacks()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo :message', '*')->andReturn([]);
        $this->assertSame('foo ', $t->get('foo :message', ['message' => null]));
    }

    public function testDetermineLocalesUsingMethod()
    {
        $t = new Translator($this->getLoader(), 'en');
        $t->determineLocalesUsing(function ($locales) {
            $this->assertSame(['en', 'en'], $locales);

            return ['en', 'lz'];
        });
        $t->getLoader()->shouldReceive('load')->once()->with('en', '*', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('en', 'foo', '*')->andReturn([]);
        $t->getLoader()->shouldReceive('load')->once()->with('lz', 'foo', '*')->andReturn([]);
        $this->assertSame('foo', $t->get('foo'));
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
        $this->assertEquals(
            'mom',
            $this->translator->choice('lang.test.mother', 1)
        );
        $this->assertEquals(
            'moms',
            $this->translator->choice('lang.test.mother', 2)
        );

        $this->translator->setLocale('en-gb');

        $this->assertEquals(
            'mum',
            $this->translator->choice('lang.test.mother', 1)
        );

        $this->assertEquals(
            'mums',
            $this->translator->choice('lang.test.mother', 2)
        );
    }

    public function testNamespaceAliasing()
    {
        $this->translator->registerNamespaceAlias('winter.test', 'winter.alias');
        $this->assertEquals('Hello Winter!', $this->translator->get('winter.test::lang.test.hello_winter'));
        $this->assertEquals('Hello Winter!', $this->translator->get('winter.alias::lang.test.hello_winter'));
    }

    public function testMixedCaseNamespaceAliasing()
    {
        $this->translator->registerNamespaceAlias('Winter.Test', 'Winter.CaseAlias');
        $this->assertEquals('Hello Winter!', $this->translator->get('winter.test::lang.test.hello_winter'));
        $this->assertEquals('Hello Winter!', $this->translator->get('winter.casealias::lang.test.hello_winter'));
    }
}
