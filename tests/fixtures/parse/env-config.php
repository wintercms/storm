<?php

return [
    'sample' => [
        'value' => env('TEST_ENV', 'default'),
        'no_default' => env('TEST_NO_DEFAULT')
    ]
];
