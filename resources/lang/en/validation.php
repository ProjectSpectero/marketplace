<?php

return [
    'required' => \App\Constants\Errors::FIELD_REQUIRED,
    'unique' => \App\Constants\Errors::FIELD_UNIQUE,
    'min' => [
        'numeric' => \App\Constants\Errors::FIELD_MINLENGHT,
        'string' => \App\Constants\Errors::FIELD_MINLENGHT,
    ],
    'max' => [
        'numeric' => \App\Constants\Errors::FIELD_MAXLENGHT,
        'string' => \App\Constants\Errors::FIELD_MAXLENGHT,
    ]
];