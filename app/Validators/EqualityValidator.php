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

        // VERY DIRTY HACK TO WORK AROUND LARAVEL NOT PASSING IN THE 'false' value
        //if (empty($value))
        //   $value = false;

        if (is_bool($value))
            $param = ($param == 'true') ? true : false;

        //if ($attribute == 'config.LocalSubnetBanEnabled')
            //dd($value, $param, $value === $param);

        // Otherwise standard String comparison
        return $value === $param;
    }
}