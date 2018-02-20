<?php


namespace App\Validators;

use App\Constants\Errors;
use App\Errors\UserFriendlyException;

class EqualityValidator
{
    public function validate ($attribute, $value, $parameters)
    {
        $param = isset($parameters[0]) ? $parameters[0] : null;

        if ($param == null)
            throw new UserFriendlyException(Errors::FIELD_REQUIRED);

        // TODO: the source of truth should actually be the param instead of the value
        // Problem is, it's always returned as a string. Keep this in mind if the equality check is used for more than bool/strings though

        if (is_bool($value))
            $param = ($param == 'true') ? true : false;

        // Otherwise standard String comparison
        return $value === $param;
    }
}