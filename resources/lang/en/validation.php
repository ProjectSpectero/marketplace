<?php

return [
    'required' => \App\Constants\Errors::FIELD_REQUIRED . '::attribute:',
    'email' => \App\Constants\Errors::FIELD_EMAIL . '::attribute:',
    'unique' => \App\Constants\Errors::FIELD_UNIQUE . '::attribute:',
    'alpha_num' => \App\Constants\Errors::FIELD_ALPHANUM . '::attribute:',
    'alpha_dash' => \App\Constants\Errors::FIELD_ALPHADASH . '::attribute:',
    'min' => [
        'numeric' => \App\Constants\Errors::FIELD_MINLENGTH . '::attribute::min',
        'string' => \App\Constants\Errors::FIELD_MINLENGTH . '::attribute::min',
    ],
    'max' => [
        'numeric' => \App\Constants\Errors::FIELD_MAXLENGTH . '::attribute::max',
        'string' => \App\Constants\Errors::FIELD_MAXLENGTH . '::attribute::max',
    ],
    'country' => \App\Constants\Errors::FIELD_COUNTRY . '::attribute:',
    'boolean' => \App\Constants\Errors::FIELD_BOOLEAN . '::attribute:'
];