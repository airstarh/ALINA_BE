<?php
return (object)[
    'simpleArray' => [1, 2, 3, 'a string'],
    'assocArray'  => [
        'astring' => 'astring',
        'anumber' => 256,
    ],
    'number'      => 128,
    'afloat'      => 10.128,
    'string'      => 'Hello, complicated object',
    'serialized'      => 'O:8:"stdClass":5:{s:11:"simpleArray";a:4:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;s:8:"a string";}s:10:"assocArray";a:2:{s:7:"astring";s:7:"astring";s:7:"anumber";i:256;}s:6:"number";i:128;s:6:"afloat";d:10.128;s:6:"string";s:25:"Hello, complicated object";}',
];
