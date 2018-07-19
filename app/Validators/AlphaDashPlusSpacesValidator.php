<?php


namespace App\Validators;


class AlphaDashPlusSpacesValidator
{
    public function validate ($attribute, $value, $parameters) : bool
    {
        return preg_match('/^[\pL\-\_\s]+$/iu', $value);
    }
}