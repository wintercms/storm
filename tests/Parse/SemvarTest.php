<?php

use Winter\Storm\Parse\Semvar;

class SemvarTest extends TestCase
{
    public function testEqual()
    {
        $this->assertTrue(Semvar::match('1.0.0', '1.0.0'));
        $this->assertTrue(Semvar::match('1.0', '1.0.0'));
        $this->assertTrue(Semvar::match('1.0.0', '1.0'));
        $this->assertFalse(Semvar::match('1.0.1', '1.0.0'));
        $this->assertFalse(Semvar::match('1.0.1', '1.0'));
    }

    public function testGreaterThan()
    {
        $this->assertTrue(Semvar::match('>1.5', '1.6'));
        $this->assertFalse(Semvar::match('>1.5', '1.3'));
        $this->assertFalse(Semvar::match('>1.5', '1.5'));
    }

    public function testGreaterThanOrEqualTo()
    {
        $this->assertTrue(Semvar::match('>=1.5', '1.6'));
        $this->assertTrue(Semvar::match('>=1.5', '1.5'));
        $this->assertFalse(Semvar::match('>=1.5', '1.3'));
    }

    public function testLessThan()
    {
        $this->assertTrue(Semvar::match('<1.5', '1.3'));
        $this->assertTrue(Semvar::match('<1.5', '1'));
        $this->assertTrue(Semvar::match('<1.5', '0.5'));
        $this->assertFalse(Semvar::match('<1.5', '1.6'));
        $this->assertFalse(Semvar::match('<1.5', '2.0'));
    }

    public function testLessThanOrEqualTo()
    {
        $this->assertTrue(Semvar::match('<=1.5', '1.3'));
        $this->assertTrue(Semvar::match('<=1.5', '1.5'));
        $this->assertFalse(Semvar::match('<=1.5', '1.7'));
        $this->assertFalse(Semvar::match('<=1.5', '2.0'));
    }

    public function testOrRule()
    {
        $this->assertTrue(Semvar::match('1.0 || 1.5', '1.5'));
        $this->assertTrue(Semvar::match('1.0 || 1.5', '1.0.0'));
        $this->assertFalse(Semvar::match('1.0 || 1.5', '1.3'));
    }

    public function testBoundariesRule()
    {
        $this->assertTrue(Semvar::match('>=1.0 <1.5', '1.3'));
        $this->assertTrue(Semvar::match('>=1.0 <1.5', '1.3.6'));
        $this->assertFalse(Semvar::match('>=1.0 <1.5', '1.6'));
        $this->assertFalse(Semvar::match('>=1.0 <1.5', '0.6.1'));
    }

    public function testBoundariesWithOrRule()
    {
        $this->assertTrue(Semvar::match('>=1.0 <1.4 || >=1.6', '1.6'));
        $this->assertTrue(Semvar::match('>=1.0 <1.4 || >=1.6', '1.3.2'));
        $this->assertTrue(Semvar::match('>=1.0 <1.4 || >=1.6', '1.7'));
        $this->assertFalse(Semvar::match('>=1.0 <1.4 || >=1.6', '1.5.9'));
        $this->assertFalse(Semvar::match('>=1.0 <1.4 || >=1.6', '0.5'));
    }

    public function testTildyRule()
    {
        $this->assertTrue(Semvar::match('~1.5', '1.6'));
        $this->assertTrue(Semvar::match('~1.0', '2.0'));
        $this->assertTrue(Semvar::match('~1.0', '1.0'));
        $this->assertFalse(Semvar::match('~1.5.1', '1.6'));
        $this->assertFalse(Semvar::match('~1.6', '2.0'));
    }

    public function testTildyWithOrRule()
    {
        $this->assertTrue(Semvar::match('~1.5 || ~3.2', '1.6'));
        $this->assertTrue(Semvar::match('~1.5 || ~3.2', '3.3.0'));
        $this->assertFalse(Semvar::match('~1.5 || ~3.2', '2.0'));
        $this->assertFalse(Semvar::match('~1.5 || ~3.2', '2.5'));
    }

    public function testCaretRule()
    {
        $this->assertTrue(Semvar::match('^1.5', '1.6'));
        $this->assertTrue(Semvar::match('^1.0', '2.0'));
        $this->assertTrue(Semvar::match('^0.4.0', '0.4.1'));
        $this->assertTrue(Semvar::match('^0.4.0', '0.4'));
        $this->assertFalse(Semvar::match('^1.5.1', '3.0'));
        $this->assertFalse(Semvar::match('^1.6', '2.1.0'));
        $this->assertFalse(Semvar::match('^1.6', '0.1'));
        $this->assertFalse(Semvar::match('^0.4.0', '1.0'));
        $this->assertFalse(Semvar::match('^0.4.0', '0.2.0'));
    }

    public function testCaretWithOrRule()
    {
        $this->assertTrue(Semvar::match('^1.5 || ^3.2', '1.6'));
        $this->assertTrue(Semvar::match('^1.5 || ^3.2', '3.3.0'));
        $this->assertFalse(Semvar::match('^1.5 || ^3.2', '4.1'));
        $this->assertFalse(Semvar::match('^1.5 || ^0.4', '2.5'));
        $this->assertFalse(Semvar::match('^1.5 || ^0.4', '0.3.9'));
    }

    public function testMixedRule()
    {
        $this->assertTrue(Semvar::match('~1.5 || ^3.2', '1.6'));
        $this->assertTrue(Semvar::match('>=1.5 || ^3.2', '3.3.0'));
        $this->assertTrue(Semvar::match('>=1.5 <=2.0 || ^3.2', '1.6'));
        $this->assertFalse(Semvar::match('~1.5 || ^3.2', '5.2'));
        $this->assertFalse(Semvar::match('>=1.5 || ^3.2', '1.3.0'));
        $this->assertFalse(Semvar::match('>=1.5 <=2.0 || ^3.2', '1.3'));
    }

    public function testExplode()
    {
        $version = Semvar::explode('1.5');

        $this->assertIsArray($version);

        $this->assertArrayHasKey('string', $version);
        $this->assertArrayHasKey('major', $version);
        $this->assertArrayHasKey('minor', $version);
        $this->assertArrayHasKey('patch', $version);

        $this->assertIsString($version['string']);
        $this->assertIsInt($version['major']);
        $this->assertIsInt($version['minor']);
        $this->assertIsInt($version['patch']);

        $this->assertEquals('1.5.0', $version['string']);
        $this->assertEquals(1, $version['major']);
        $this->assertEquals(5, $version['minor']);
        $this->assertEquals(0, $version['patch']);
    }
}
