<?php

return [
    'required' => \App\Constants\Errors::FIELD_REQUIRED . '::attribute:',
    'email' => \App\Constants\Errors::FIELD_EMAIL . '::attribute:',
    'unique' => \App\Constants\Errors::FIELD_UNIQUE . '::attribute:',
    'alpha_num' => \App\Constants\Errors::FIELD_ALPHANUM . '::attribute:',
    'alpha_dash' => \App\Constants\Errors::FIELD_ALPHADASH . '::attribute:',
    'alpha_dash_spaces' => \App\Constants\Errors::FIELD_ALPHADASHSPACES . '::attribute:',
    'array' => \App\Constants\Errors::FIELD_OBJECT . '::attribute:',
    'min' => [
        'numeric' => \App\Constants\Errors::FIELD_MINVALUE . '::attribute::min',
        'string' => \App\Constants\Errors::FIELD_MINLENGTH . '::attribute::min',
    ],
    'max' => [
        'numeric' => \App\Constants\Errors::FIELD_MAXVALUE . '::attribute::max',
        'string' => \App\Constants\Errors::FIELD_MAXLENGTH . '::attribute::max',
    ],
    'country' => \App\Constants\Errors::FIELD_COUNTRY . '::attribute',
    'boolean' => \App\Constants\Errors::FIELD_BOOLEAN . '::attribute',
    'in' => \App\Constants\Errors::FIELD_IN . '::attribute',
    'between' => [
        'string' => \App\Constants\Errors::FIELD_BETWEEN . '::attribute::min::max',
        'numeric' => \App\Constants\Errors::FIELD_BETWEEN . '::attribute::min::max',
    ],
    'equals' => \App\Constants\Errors::FIELD_EQUALS . '::attribute',
    'regex' => \App\Constants\Errors::FIELD_REGEX . '::attribute',
    'date_format' => \App\Constants\Errors::FIELD_INVALID . '::attribute' . ':Y-m-d',
];