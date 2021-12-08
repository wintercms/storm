<?php

use Winter\Storm\Support\Svg;

class SvgTest extends TestCase
{
    public function testCleanSvg()
    {
        $svg = Svg::extract(dirname(__DIR__) . '/fixtures/svg/winter.svg');
        $fixture = trim(file_get_contents(dirname(__DIR__) . '/fixtures/svg/extracted/winter.svg'));

        $this->assertEquals($fixture, $svg);
    }

    public function testDirtySvg()
    {
        $svg = Svg::extract(dirname(__DIR__) . '/fixtures/svg/winter-dirty.svg');
        $fixture = trim(file_get_contents(dirname(__DIR__) . '/fixtures/svg/extracted/winter-dirty.svg'));

        $this->assertEquals($fixture, $svg);
    }
}
