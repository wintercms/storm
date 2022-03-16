<?php

function winterTest(string $str)
{
    return $str . ' Winter';
}

return [
    'foo' => winterTest('foo')
];
