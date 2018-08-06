<?php


namespace App\Validators;


class AlphaDashPlusSpacesValidator
{
    public function validate ($attribute, $value, $parameters) : bool
    {
        // See https://regex101.com/r/wJZSen/1
        return preg_match('/^(?! )[\pL0-9\-\_\s]+(?<! )$/iu', $value);
    }
}