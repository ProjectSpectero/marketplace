<?php

return [
    'required' => \App\Constants\Errors::FIELD_REQUIRED,
    'unique' => \App\Constants\Errors::FIELD_UNIQUE,
    'min' => [
        'numeric' => \App\Constants\Errors::FIELD_MINLENGHT . ' > :min',
        'string' => \App\Constants\Errors::FIELD_MINLENGHT . ' > :min',
    ],
    'max' => [
        'numeric' => \App\Constants\Errors::FIELD_MAXLENGHT . ' < :max',
        'string' => \App\Constants\Errors::FIELD_MAXLENGHT . ' < :max',
    ]
];