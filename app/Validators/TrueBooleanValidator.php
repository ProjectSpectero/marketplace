<?php


namespace App\Validators;


class TrueBooleanValidator
{
    public function validate ($attribute, $value, $parameters) : bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
    }
}