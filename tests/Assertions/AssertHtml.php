<?php

namespace Winter\Storm\Tests\Assertions;

use DOMElement;
use PHPUnit\Framework\Assert;

trait AssertHtml
{
    public function assertElementIs($expected, $html, $message = '')
    {
        $constraint = Assert::callback(function ($expected) use ($html) {
            $element = $this->parseElement($html);

            return strtolower($element->tagName) === strtolower($expected);
        });

        Assert::assertThat($expected, $constraint, $message ?: 'Failed asserting that HTML is a "' . $expected . '" tag.');
    }

    public function assertElementHasAttribute($attribute, $html, $message = '')
    {
        Assert::assertTrue(
            $this->elementHasAttribute($attribute, $html),
            $message ?: 'Failed asserting that HTML contains attribute "' . $attribute . '".'
        );
    }

    public function assertElementDoesntHaveAttribute($attribute, $html, $message = '')
    {
        Assert::assertFalse(
            $this->elementHasAttribute($attribute, $html),
            $message ?: 'Failed asserting that HTML does not contain attribute "' . $attribute . '".'
        );
    }

    public function assertElementAttributeEquals($attribute, $expected, $html, $message = '')
    {
        $constraint = Assert::callback(function ($expected) use ($attribute, $html) {
            $element = $this->parseElement($html);

            if (!$element->hasAttribute($attribute)) {
                Assert::fail('HTML attribute "' . $attribute . '" does not exist');
            }

            return $element->getAttribute($attribute) === $expected;
        });

        Assert::assertThat($expected, $constraint, $message ?: 'Failed asserting that attribute "' . $attribute . '" equals "' . $expected . '".');
    }

    public function assertElementAttributeNotEquals($attribute, $expected, $html, $message = '')
    {
        $constraint = Assert::callback(function ($expected) use ($attribute, $html) {
            $element = $this->parseElement($html);

            if (!$element->hasAttribute($attribute)) {
                Assert::fail('HTML attribute "' . $attribute . '" does not exist');
            }

            return $element->getAttribute($attribute) !== $expected;
        });

        Assert::assertThat($expected, $constraint, $message ?: 'Failed asserting that attribute "' . $attribute . '" does not equal "' . $expected . '".');
    }

    public function assertElementContainsText($expected, $html, $message = '')
    {
        $constraint = Assert::callback(function ($expected) use ($html) {
            $element = $this->parseElement($html);

            return trim($element->textContent) === trim($expected);
        });

        Assert::assertThat($expected, $constraint, $message ?: 'Failed asserting that HTML element contains the text "' . $expected . '".');
    }

    public function assertElementDoesntContainText($expected, $html, $message = '')
    {
        $constraint = Assert::callback(function ($expected) use ($html) {
            $element = $this->parseElement($html);

            return trim($element->textContent) !== trim($expected);
        });

        Assert::assertThat($expected, $constraint, $message ?: 'Failed asserting that HTML element does not contain the text "' . $expected . '".');
    }

    protected function elementHasAttribute($attribute, $html)
    {
        $element = $this->parseElement($html);

        return $element->hasAttribute($attribute);
    }

    protected function parseElement($html): DOMElement
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Ensure HTML is read as UTF-8
        $dom->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>');

        $body = $dom->getElementsByTagName('body');
        $body = $body->item(0);

        if ($body->childNodes->length > 1) {
            Assert::fail('HTML contains more than one HTML element.');
        }

        return $body->childNodes->item(0);
    }
}
