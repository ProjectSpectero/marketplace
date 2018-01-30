<?php

return [
    'required' => \App\Constants\Errors::FIELD_REQUIRED,
    'min' => [
        'numeric' => \App\Constants\Errors::FIELD_MINLENGHT . '::attribute' .'::min',
        'string' => \App\Constants\Errors::FIELD_MINLENGHT . '::attribute' . '::min',
    ],
    'max' => [
        'numeric' => \App\Constants\Errors::FIELD_MAXLENGHT . '::attribute' .'::min',
        'string' => \App\Constants\Errors::FIELD_MAXLENGHT . '::attribute' . '::min',
    ]
];