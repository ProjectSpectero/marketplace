<?php

return [
    'required' => \App\Constants\Errors::FIELD_REQUIRED . '::attribute:',
    'email' => \App\Constants\Errors::FIELD_EMAIL . '::attribute:',
    'unique' => \App\Constants\Errors::FIELD_UNIQUE . '::attribute:',
    'min' => [
        'numeric' => \App\Constants\Errors::FIELD_MINLENGTH . '::attribute::min',
        'string' => \App\Constants\Errors::FIELD_MINLENGTH . '::attribute::min',
    ],
    'max' => [
        'numeric' => \App\Constants\Errors::FIELD_MAXLENGTH . '::attribute::max',
        'string' => \App\Constants\Errors::FIELD_MAXLENGTH . '::attribute::max',
    ]
];