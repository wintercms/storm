<?php

use Winter\Storm\Parse\Ini as IniParser;

class IniTest extends TestCase
{

    public function testBasic()
    {
        $path = __DIR__.'/../fixtures/parse/basic.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $vars = [
            'title' => 'Plugin components',
            'url' => '/demo/plugins',
            'layout' => 'default',
            'demoTodo' => [
                'min' => 1.2,
                'max' => 3
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertCount(4, $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('layout', $result);
        $this->assertEquals('Plugin components', $result['title']);
        $this->assertEquals('/demo/plugins', $result['url']);
        $this->assertEquals('default', $result['layout']);
        $this->assertArrayHasKey('demoTodo', $result);
        $this->assertArrayHasKey('max', $result['demoTodo']);
        $this->assertArrayHasKey('min', $result['demoTodo']);
        $this->assertEquals(1.2, $result['demoTodo']['min']);
        $this->assertEquals(3, $result['demoTodo']['max']);
        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testArray()
    {
        $path = __DIR__.'/../fixtures/parse/array.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $vars = [
            'products' => [
                'excludeStatuses' => [1, 42, 69]
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertArrayHasKey('products', $result);
        $this->assertArrayHasKey('excludeStatuses', $result['products']);
        $this->assertCount(3, $result['products']['excludeStatuses']);
        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testObject()
    {
        $path = __DIR__.'/../fixtures/parse/object.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $vars = [
            'viewBag' => [
                'code' => 'signin-snippet',
                'name' => 'Sign in snippet',
                'properties' => [
                    'type' => 'string',
                    'title' => 'Redirection page',
                    'default' => '/clients'
                ]
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertArrayHasKey('viewBag', $result);
        $this->assertArrayHasKey('properties', $result['viewBag']);
        $this->assertArrayHasKey('type', $result['viewBag']['properties']);
        $this->assertArrayHasKey('title', $result['viewBag']['properties']);
        $this->assertArrayHasKey('default', $result['viewBag']['properties']);
        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testComments()
    {
        $path = __DIR__.'/../fixtures/parse/comments.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $vars = [
            'owner' => [
                'name' => 'John Doe',
                'organization' => 'Acme Widgets Inc.',
            ],
            'database' => [
                'server' => '192.0.2.62',
                'port' => '143',
                'file' => 'payroll.dat',
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertArrayHasKey('owner', $result);
        $this->assertArrayHasKey('name', $result['owner']);
        $this->assertArrayHasKey('organization', $result['owner']);
        $this->assertArrayHasKey('database', $result);
        $this->assertArrayHasKey('server', $result['database']);
        $this->assertArrayHasKey('port', $result['database']);
        $this->assertArrayHasKey('file', $result['database']);
        $this->assertEquals($vars, $result);

        $path = __DIR__.'/../fixtures/parse/comments-clean.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testComplex()
    {
        $path = __DIR__.'/../fixtures/parse/complex.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $vars = [
            'firstLevelValue' => 'relax',
            'firstLevelArray' => ['foo', 'bar'],
            'someComponent' => [
                'secondLevelArray' => ['hello', 'world'],
                'name' => [
                    'title' => 'column_name_name',
                    'validation' => [
                        'required' => [
                            'message' => 'column_name_required'
                        ],
                        'regex' => [
                            'pattern' => '^[0-9_a-z]+$',
                            'message' => 'column_validation_title'
                        ]
                    ]
                ],
                'type' => [
                    'title' => 'column_name_type',
                    'type' => 'dropdown',
                    'options' => [
                        'integer' => 'Integer',
                        'smallInteger' => 'Small Integer',
                        'bigInteger' => 'Big Integer',
                        'date' => 'Date',
                        'time' => 'Time',
                        'dateTime' => 'Date and Time',
                        'timestamp' => 'Timestamp',
                        'string' => 'String',
                        'text' => 'Text',
                        'binary' => 'Binary',
                        'boolean' => 'Boolean',
                        'decimal' => 'Decimal',
                        'double' => 'Double'
                    ],
                    'validation' => [
                        'required' => [
                            'message' => 'column_type_required'
                        ]
                    ]
                ],
                'modes' => [
                    'title' => 'column_name_type',
                    'type' => 'checkboxlist',
                    'options' => [12, 34, 56, 78, 99]
                ],
                'security' => [
                    'title' => 'column_name_security',
                    'type' => 'radio',
                    'options' => [
                        'all' => ['All', 'Everyone'],
                        'users' => ['Users', 'Users only'],
                        'guests' => ['Guests', 'Guests only']
                    ]
                ],
                'length' => [
                    'title' => 'column_name_length',
                    'validation' => [
                        'regex' => [
                            'pattern' => '(^[0-9]+$)|(^[0-9]+,[0-9]+$)',
                            'message' => 'column_validation_length'
                        ]
                    ]
                ],
                'unsigned' => [
                    'title' => 'column_name_unsigned',
                    'type' => 'checkbox'
                ],
                'allow_null' => [
                    'title' => 'column_name_nullable',
                    'type' => 'checkbox'
                ],
                'auto_increment' => [
                    'title' => 'column_auto_increment',
                    'type' => 'checkbox'
                ],
                'primary_key' => [
                    'title' => 'column_auto_primary_key',
                    'type' => 'checkbox',
                    'width' => '50px'
                ],
                'default' => [
                    'title' => 'column_default'
                ]
            ]
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $this->assertEquals($content, $result);
    }

    public function testMultilinesValues()
    {
        $path = __DIR__.'/../fixtures/parse/multilines-value.ini';
        $this->assertFileExists($path);
        $content = $this->getContents($path);

        $vars = [
            'var' => "\\Test\\Path\\",
            'editorContent' =>
                "<p>Some\n" .
                "    <br>\"Multi-line\"\n" .
                "    <br>text\n" .
                "</p>",
        ];

        $parser = new IniParser;
        $result = $parser->parse($content);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('var', $result);
        $this->assertArrayHasKey('editorContent', $result);

        // Ensures we do not care about EOL sequences
        $result['editorContent'] = str_replace("\r\n", "\n", $result['editorContent']);
        $vars['editorContent'] = str_replace("\r\n", "\n", $vars['editorContent']);

        $this->assertEquals($vars, $result);

        $result = $parser->render($vars);
        $content = str_replace("\r\n", "\n", $content);
        $result = str_replace("\r\n", "\n", $result);
        $this->assertEquals($content, $result);
    }

    public function testRender()
    {
        $parser = new IniParser;

        $data = [
            'var1'=>'value 1',
            'var2'=>'value 21'
        ];

        $path = __DIR__.'/../fixtures/parse/simple.ini';
        $this->assertFileExists($path);

        $str = $parser->render($data);

        $this->assertNotEmpty($str);
        $this->assertEquals($this->getContents($path), $str);

        $data = [
            'section' => [
                'sectionVar1' => 'section value 1',
                'sectionVar2' => 'section value 2'
            ],
            'section data' => [
                'sectionVar3' => 'section value 3',
                'sectionVar4' => 'section value 4'
            ],
            'emptysection' => [],
            'var1'=>'value 1',
            'var2'=>'value 21'
        ];

        $path = __DIR__.'/../fixtures/parse/sections.ini';
        $this->assertFileExists($path);

        $str = $parser->render($data);
        $this->assertEquals($this->getContents($path), $str);

        $data = [
            'section' => [
                'sectionVar1' => 'section value 1',
                'sectionVar2' => 'section value 2',
                'subsection' => [
                    'subsection value 1',
                    'subsection value 2'
                ],
                'sectionVar3' => 'section value 3'
            ],
            'section data' => [
                'sectionVar3' => 'section value 3',
                'sectionVar4' => 'section value 4',
                'subsection' => [
                    'subsection value 1',
                    'subsection value 2'
                ]
            ],
            'var1'=>'value 1',
            'var2'=>'value 21'
        ];

        $path = __DIR__.'/../fixtures/parse/subsections.ini';
        $this->assertFileExists($path);

        $str = $parser->render($data);
        $this->assertEquals($this->getContents($path), $str);
    }

    /**
     * Regression test for CVE-2026-25125 — ensure the INI parser does not
     * interpolate PHP ${VAR} environment-variable syntax, which would allow
     * an attacker with editor access to exfiltrate secrets such as APP_KEY
     * or DB_PASSWORD via CMS template settings.
     */
    public function testEnvironmentVariableInterpolationIsDisabled()
    {
        $canaryName = 'WINTER_CVE_2026_25125_CANARY';
        $canaryValue = 'LEAK_ME_IF_BROKEN';
        putenv($canaryName . '=' . $canaryValue);

        try {
            $parser = new IniParser;

            $contents = <<<INI
title = "\${{$canaryName}}"
url = "/demo/\${{$canaryName}}"
nested[key] = "\${{$canaryName}}"
INI;

            $result = $parser->parse($contents);

            $literal = '${' . $canaryName . '}';
            $this->assertSame($literal, $result['title']);
            $this->assertSame('/demo/' . $literal, $result['url']);
            $this->assertSame($literal, $result['nested']['key']);

            // Belt-and-braces: the secret value must not appear anywhere in
            // the parsed result, even nested or URL-encoded.
            $flattened = print_r($result, true);
            $this->assertStringNotContainsString($canaryValue, $flattened);
        } finally {
            putenv($canaryName);
        }
    }

    /**
     * A token in a value that happens to match the name of a defined PHP
     * constant is part of the document, not a reference to that constant, so
     * the parser must return it as written.
     */
    public function testPhpConstantNamesAreNotSubstituted()
    {
        $name = 'WINTER_INI_TEST_CONSTANT';
        if (!defined($name)) {
            define($name, 'constant-value');
        }

        $parser = new IniParser;

        $contents = <<<INI
plain = {$name}
spaced = {$name} {$name}
adjacent = {$name}"x"
list[] = {$name}
list[] = other
commented = {$name} ; {$name}

[{$name}]
nested = {$name}
{$name} = literalKey
INI;

        $result = $parser->parse($contents);

        $this->assertSame($name, $result['plain']);
        $this->assertSame($name . ' ' . $name, $result['spaced']);
        $this->assertSame($name . 'x', $result['adjacent']);
        $this->assertSame([$name, 'other'], $result['list']);
        $this->assertSame($name, $result['commented']);

        // Section headers and key names were never substituted; confirm the
        // pre-pass has not changed that.
        $this->assertArrayHasKey($name, $result);
        $this->assertSame($name, $result[$name]['nested']);
        $this->assertSame('literalKey', $result[$name][$name]);

        $this->assertStringNotContainsString(
            constant($name),
            print_r($result, true)
        );
    }

    /**
     * PHP_VERSION and PHP_BINARY are defined in every process, so they are the
     * honest guard: a document naming them gets its own text back.
     */
    public function testInterpreterConstantsAreNotSubstituted()
    {
        $parser = new IniParser;

        $result = $parser->parse("version = PHP_VERSION\nbinary = PHP_BINARY\n");

        $this->assertSame('PHP_VERSION', $result['version']);
        $this->assertSame('PHP_BINARY', $result['binary']);
    }

    /**
     * A quoted value naming a defined constant is returned verbatim, in single
     * or double quotes and as part of a longer sentence.
     */
    public function testQuotedConstantNamesAreUnchanged()
    {
        $name = 'WINTER_INI_TEST_CONSTANT';
        if (!defined($name)) {
            define($name, 'constant-value');
        }

        $parser = new IniParser;

        $contents = <<<INI
double = "{$name}"
single = '{$name}'
sentence = "the {$name} token"
INI;

        $result = $parser->parse($contents);

        $this->assertSame($name, $result['double']);
        $this->assertSame($name, $result['single']);
        $this->assertSame('the ' . $name . ' token', $result['sentence']);
    }

    /**
     * The INI keywords keep their coercion; they are not identifiers as far as
     * the pre-pass is concerned.
     */
    public function testReservedKeywordsAreStillCoerced()
    {
        $parser = new IniParser;

        $contents = <<<'INI'
a = true
b = false
c = on
d = off
e = yes
f = no
g = none
h = null
i = TRUE
j = Off
k = "true"
l = truthy
INI;

        $result = $parser->parse($contents);

        $this->assertSame('1', $result['a']);
        $this->assertSame('', $result['b']);
        $this->assertSame('1', $result['c']);
        $this->assertSame('', $result['d']);
        $this->assertSame('1', $result['e']);
        $this->assertSame('', $result['f']);
        $this->assertSame('', $result['g']);
        $this->assertSame('', $result['h']);
        $this->assertSame('1', $result['i']);
        $this->assertSame('', $result['j']);
        $this->assertSame('true', $result['k']);
        $this->assertSame('truthy', $result['l']);
    }

    /**
     * Multi-line quoted values must still parse, including when a continuation
     * line contains an `=` or a token matching a defined constant.
     */
    public function testMultilineQuotedValuesAreUnaffected()
    {
        $name = 'WINTER_INI_TEST_CONSTANT';
        if (!defined($name)) {
            define($name, 'constant-value');
        }

        $parser = new IniParser;

        $contents = <<<'INI'
var = "\Test\Path\"
editorContent = "<p>Some
    <br>\"Multi-line\"""
    <br>key = WINTER_INI_TEST_CONSTANT
</p>"
after = WINTER_INI_TEST_CONSTANT
INI;

        $result = $parser->parse($contents);

        $expected = "<p>Some\n"
            . "    <br>\"Multi-line\"\n"
            . "    <br>key = {$name}\n"
            . '</p>';

        $this->assertSame('\\Test\\Path\\', $result['var']);

        $this->assertSame(
            $expected,
            str_replace("\r\n", "\n", $result['editorContent'])
        );
        $this->assertSame($name, $result['after']);
    }

    /**
     * A rendered document round-trips: what render() writes, parse() reads back
     * unchanged, including a value that names a defined constant.
     */
    public function testRenderedConstantNameRoundTrips()
    {
        $name = 'WINTER_INI_TEST_CONSTANT';
        if (!defined($name)) {
            define($name, 'constant-value');
        }

        $parser = new IniParser;

        $vars = [
            'plain' => $name,
            'sentence' => 'the ' . $name . ' token',
            'section' => [
                'nested' => $name,
                'list' => [$name, 'other'],
            ],
        ];

        $this->assertSame($vars, $parser->parse($parser->render($vars)));
    }

    /**
     * A constant name used inside a bracketed key is substituted by the native
     * parser just as one in a value is, so it has to be neutralized too - the
     * document's own text is what belongs in the resulting array key.
     */
    public function testConstantNamesInBracketedKeysAreNotSubstituted()
    {
        $name = 'WINTER_INI_TEST_CONSTANT';
        if (!defined($name)) {
            define($name, 'constant-value');
        }

        $parser = new IniParser;

        $contents = <<<INI
one[{$name}] = x
two[{$name}]=y
three[PHP_BINARY] = z
four[b][{$name}] = deep
five[{$name}][] = appended

[section]
six[{$name}] = inSection
INI;

        $result = $parser->parse($contents);

        $this->assertSame(['x'], array_values($result['one']));
        $this->assertArrayHasKey($name, $result['one']);
        $this->assertArrayHasKey($name, $result['two']);
        $this->assertArrayHasKey('PHP_BINARY', $result['three']);
        $this->assertArrayHasKey($name, $result['four']['b']);
        $this->assertArrayHasKey($name, $result['five']);
        $this->assertArrayHasKey($name, $result['section']['six']);

        $this->assertStringNotContainsString(constant($name), print_r($result, true));
        $this->assertStringNotContainsString(PHP_BINARY, print_r($result, true));
    }

    /**
     * The marker the parser uses internally is not part of the INI syntax, so a
     * document that happens to contain it keeps it, in a value and in a key,
     * and a rendered document still round-trips.
     */
    public function testDocumentTextMatchingTheInternalMarkerIsPreserved()
    {
        $marker = (new ReflectionClass(IniParser::class))->getConstant('IDENTIFIER_PLACEHOLDER');
        $this->assertIsString($marker);

        $parser = new IniParser;

        $contents = <<<INI
prefixed = {$marker}hello
alone = {$marker}
quoted = "{$marker}hello"
doubled = {$marker}{$marker}hello
{$marker}key = inKey
INI;

        $result = $parser->parse($contents);

        $this->assertSame($marker . 'hello', $result['prefixed']);
        $this->assertSame($marker, $result['alone']);
        $this->assertSame($marker . 'hello', $result['quoted']);
        $this->assertSame($marker . $marker . 'hello', $result['doubled']);
        $this->assertArrayHasKey($marker . 'key', $result);

        $vars = ['plain' => $marker . 'hello'];
        $this->assertSame($vars, $parser->parse($parser->render($vars)));
    }

    /**
     * A bracketed key offset is the one position where the native parser
     * resolves a token named after an INI keyword against the constant table
     * rather than coercing it, so those have to be neutralized there as well,
     * while the coercion of the same words in a value stays untouched.
     */
    public function testKeywordNamedConstantsInBracketedKeysAreNotSubstituted()
    {
        foreach (['yes', 'no', 'none', 'on', 'off'] as $keyword) {
            if (!defined($keyword)) {
                define($keyword, 'keyword-constant-value');
            }
        }

        $parser = new IniParser;

        $contents = <<<'INI'
a[yes] = one
b[no] = two
c[none] = three
d[on] = four
e[off] = five
f[YES] = six
g[None] = seven
h[none][off] = eight
coercedValue = none
coercedOther = yes
INI;

        $result = $parser->parse($contents);

        $this->assertSame(['yes' => 'one'], $result['a']);
        $this->assertSame(['no' => 'two'], $result['b']);
        $this->assertSame(['none' => 'three'], $result['c']);
        $this->assertSame(['on' => 'four'], $result['d']);
        $this->assertSame(['off' => 'five'], $result['e']);
        $this->assertSame(['none' => ['off' => 'eight']], $result['h']);

        // Constant names are case-sensitive, so a differently cased keyword
        // names no constant and was never substituted either.
        $this->assertSame(['YES' => 'six'], $result['f']);
        $this->assertSame(['None' => 'seven'], $result['g']);

        // A value is still coerced exactly as it is without these constants.
        $this->assertSame('', $result['coercedValue']);
        $this->assertSame('1', $result['coercedOther']);

        $this->assertStringNotContainsString('keyword-constant-value', print_r($result, true));
    }

    /**
     * The native scanner skips horizontal whitespace between the opening
     * bracket and the offset before it resolves the offset against the constant
     * table, so an indented offset needs the same treatment as a tight one.
     */
    public function testKeywordNamedConstantsInIndentedBracketedKeysAreNotSubstituted()
    {
        foreach (['yes', 'no', 'none', 'on', 'off'] as $keyword) {
            if (!defined($keyword)) {
                define($keyword, 'keyword-constant-value');
            }
        }

        $parser = new IniParser;

        $contents = <<<'INI'
a[ yes] = one
b[  no] = two
c[	none] = three
d[ on]=four
e[ off] = five

[section]
f[ yes] = six
INI;

        $result = $parser->parse($contents);

        $this->assertSame(['yes' => 'one'], $result['a']);
        $this->assertSame(['no' => 'two'], $result['b']);
        $this->assertSame(['none' => 'three'], $result['c']);
        $this->assertSame(['on' => 'four'], $result['d']);
        $this->assertSame(['off' => 'five'], $result['e']);
        $this->assertSame(['yes' => 'six'], $result['section']['f']);

        $this->assertStringNotContainsString('keyword-constant-value', print_r($result, true));
    }

    /**
     * A failed pre-pass must be reported rather than handed on, because an
     * unmarked document would have its tokens resolved against the constant
     * table and a null one parses as nothing at all.
     */
    public function testAFailedIdentifierPrePassIsReported()
    {
        if (!defined('WINTER_INI_TEST_PREPASS_CONSTANT')) {
            define('WINTER_INI_TEST_PREPASS_CONSTANT', 'value');
        }

        $parser = new IniParser;

        $method = new \ReflectionMethod($parser, 'escapeIdentifiers');
        $method->setAccessible(true);

        $limit = ini_get('pcre.backtrack_limit');
        ini_set('pcre.backtrack_limit', '1');

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessageMatches('/Unable to parse INI contents/');

            $method->invoke($parser, "a = WINTER_INI_TEST_PREPASS_CONSTANT\n");
        } finally {
            ini_set('pcre.backtrack_limit', $limit);
        }
    }

    /**
     * `true`, `false` and `null` are coerced in a bracketed key offset too, so
     * that position is left alone for them.
     */
    public function testKeywordsCoercedInBracketedKeysAreUnchanged()
    {
        $parser = new IniParser;

        $result = $parser->parse("a[true] = x\nb[false] = y\nc[null] = z\n");

        $this->assertSame([1 => 'x'], $result['a']);
        $this->assertSame([0 => 'y'], $result['b']);
        $this->assertSame([0 => 'z'], $result['c']);
    }

    /**
     * Whether a keyword in a value is coerced is decided by that value's exact
     * text, so the pre-pass must not be able to write a marker into the middle
     * of one. A constant whose name is only part of a keyword, named elsewhere
     * in the same document, is the case that proves it.
     */
    public function testKeywordCoercionIsIndependentOfDefinedConstantNames()
    {
        $shortNames = ['ne', 'one', 'ff', 'ul', 'nul', 'al', 'es', 'ru', 'n', 'o'];

        foreach ($shortNames as $shortName) {
            if (!defined($shortName)) {
                define($shortName, 'short-constant-value');
            }
        }

        $parser = new IniParser;

        $contents = <<<'INI'
short1 = ne
short2 = one
short3 = ff
short4 = ul
short5 = nul
short6 = al
short7 = es
short8 = ru
short9 = n
short10 = o
a = true
b = false
c = on
d = off
e = yes
f = no
g = none
h = null
INI;

        $result = $parser->parse($contents);

        // Identical to the coercion asserted by
        // testReservedKeywordsAreStillCoerced, which runs with none of these
        // constants defined.
        $this->assertSame('1', $result['a']);
        $this->assertSame('', $result['b']);
        $this->assertSame('1', $result['c']);
        $this->assertSame('', $result['d']);
        $this->assertSame('1', $result['e']);
        $this->assertSame('', $result['f']);
        $this->assertSame('', $result['g']);
        $this->assertSame('', $result['h']);

        // The short names themselves are returned as written.
        foreach (array_values($shortNames) as $index => $shortName) {
            $this->assertSame($shortName, $result['short' . ($index + 1)]);
        }

        $this->assertStringNotContainsString('short-constant-value', print_r($result, true));
    }

    /**
     * A short constant name must not be marked inside a longer token either, so
     * keys, section names and number-shaped values keep their own text.
     */
    public function testShortConstantNamesAreNotMarkedInsideLongerTokens()
    {
        foreach (['id', 'n', 'e'] as $shortName) {
            if (!defined($shortName)) {
                define($shortName, 'short-constant-value');
            }
        }

        $parser = new IniParser;

        $contents = <<<'INI'
id_field = x
exponent = 1e5
mixed = 1n5
hex = 0x1A
unset[none] = y

[section_name]
nested = 1e5
INI;

        $result = $parser->parse($contents);

        $this->assertArrayHasKey('id_field', $result);
        $this->assertSame('x', $result['id_field']);
        $this->assertSame('1e5', $result['exponent']);
        $this->assertSame('1n5', $result['mixed']);
        $this->assertSame('0x1A', $result['hex']);
        $this->assertSame(['none' => 'y'], $result['unset']);
        $this->assertSame('1e5', $result['section_name']['nested']);
    }

    /**
     * restoreDollarBrace() is part of the released class, so a subclass that
     * overrides it must keep being called.
     */
    public function testReleasedRestoreHookIsStillCalled()
    {
        $parser = new class extends IniParser {
            public $hookCalls = 0;

            protected function restoreDollarBrace($value)
            {
                $this->hookCalls++;
                return parent::restoreDollarBrace($value);
            }
        };

        $result = $parser->parse("a = hello\nb = \${SOME_ENV}\n");

        $this->assertGreaterThan(0, $parser->hookCalls);
        $this->assertSame('hello', $result['a']);
        $this->assertSame('${SOME_ENV}', $result['b']);
    }

    /**
     * restoreDollarBrace() shipped as a recursive walk over the parsed array,
     * so an override that post-processes an array node has to keep being handed
     * one - the top-level array and every section array.
     */
    public function testReleasedRestoreHookStillReceivesArrayNodes()
    {
        $parser = new class extends IniParser {
            public $argumentTypes = [];

            protected function restoreDollarBrace($value)
            {
                $this->argumentTypes[] = gettype($value);
                $value = parent::restoreDollarBrace($value);

                if (is_array($value)) {
                    $value['addedByOverride'] = 'fromHook';
                }

                return $value;
            }
        };

        $result = $parser->parse("a = hello\n\n[sec]\nc = x\n");

        $this->assertContains('array', $parser->argumentTypes);
        $this->assertSame('fromHook', $result['addedByOverride']);
        $this->assertSame('fromHook', $result['sec']['addedByOverride']);
        $this->assertSame('hello', $result['a']);
        $this->assertSame('x', $result['sec']['c']);
    }

   //
   // Helpers
   //

    protected function getContents($path)
    {
        $content = file_get_contents($path);
        $content = preg_replace('~\R~u', PHP_EOL, $content); // Normalize EOL
        return $content;
    }
}
