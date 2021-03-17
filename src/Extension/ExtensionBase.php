<?php namespace Winter\Storm\Extension;

/**
 * Extension class
 * Allows for "Private traits"
 *
 * @author Alexey Bobkov, Samuel Georges
 */

class ExtensionBase
{
    use ExtensionTrait;

    public static function extend(callable $callback)
    {
        self::extensionExtendCallback($callback);
    }
}
