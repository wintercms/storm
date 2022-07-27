<?php

include __DIR__ . '/sample-array-file.php';
include_once __DIR__ . '/sample-array-file.php';
require __DIR__ . '/sample-array-file.php';
require_once __DIR__ . '/sample-array-file.php';

return [
    'foo' => array_merge(include(__DIR__ . '/sample-array-file.php'), [
        'bar' => 'foo'
    ]),
    'bar' => 'foo'
];
