<?php

return [
    'winter' => [

        // above property

        'bool' => true,
        'array' => [
            // empty array comment
        ],
        'multi_line' => [
            // empty array comment
            // with extra
        ],
        'cms' => [
            'value',
            // end of array comment
        ],
        'multi_endings' => [
            'value',
            // first line
            // last line
        ],
        'multi_comment' => [
            'value',
            /*
             * Something long
             */
        ],
        'callable' => array_merge(config('something'), [
            // configs
        ]),
    ],
];
