<?php


namespace App\Validators;


class AlphaDashPlusSpacesValidator
{
    public function validate ($attribute, $value, $parameters) : bool
    {
        return preg_match('/^(?! )[\pL0-9\-\_\s]+(?<! )$/iu', $value);
    }
}