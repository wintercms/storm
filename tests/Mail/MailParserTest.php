<?php

use Winter\Storm\Mail\MailParser;

class MailParserTest extends TestCase
{
    /**
     * Regression test for CVE-2026-25125 — mail template settings must not
     * interpolate PHP ${VAR} environment-variable syntax. Mail templates are
     * editable from the backend and previously passed straight through
     * parse_ini_string() in default scanner mode, which resolved ${APP_KEY}
     * and similar tokens against the server environment.
     */
    public function testEnvironmentVariableInterpolationIsDisabledInSettings()
    {
        $canaryName = 'WINTER_CVE_2026_25125_MAIL_CANARY';
        $canaryValue = 'LEAK_ME_IF_BROKEN';
        putenv($canaryName . '=' . $canaryValue);

        $literal = '${' . $canaryName . '}';

        try {
            // Two-section template: settings + html
            $twoSection = "subject = \"{$literal}\"\n==\n<p>Body: {$literal}</p>";
            $result = MailParser::parse($twoSection);

            $this->assertSame($literal, $result['settings']['subject']);
            $this->assertNull($result['text']);
            $this->assertSame("<p>Body: {$literal}</p>", $result['html']);
            $this->assertStringNotContainsString($canaryValue, print_r($result['settings'], true));

            // Three-section template: settings + text + html
            $threeSection = "subject = \"{$literal}\"\n==\nPlain: {$literal}\n==\n<p>Html: {$literal}</p>";
            $result = MailParser::parse($threeSection);

            $this->assertSame($literal, $result['settings']['subject']);
            $this->assertSame("Plain: {$literal}", $result['text']);
            $this->assertSame("<p>Html: {$literal}</p>", $result['html']);
            $this->assertStringNotContainsString($canaryValue, print_r($result['settings'], true));
        } finally {
            putenv($canaryName);
        }
    }

    public function testParsesBasicSettings()
    {
        $template = "subject = \"Hello world\"\nlayout = \"default\"\n==\n<p>Hi</p>";
        $result = MailParser::parse($template);

        $this->assertSame('Hello world', $result['settings']['subject']);
        $this->assertSame('default', $result['settings']['layout']);
        $this->assertSame('<p>Hi</p>', $result['html']);
        $this->assertNull($result['text']);
    }

    public function testSingleSectionIsTreatedAsHtml()
    {
        $result = MailParser::parse('<p>Only html</p>');

        $this->assertSame([], $result['settings']);
        $this->assertSame('<p>Only html</p>', $result['html']);
        $this->assertNull($result['text']);
    }
}
