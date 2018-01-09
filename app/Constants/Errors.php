<?php

namespace App\Constants;

class Errors extends Holder
{
    const SECRET_IS_REQUIRED = 'SECRET_IS_REQUIRED';
    const ERROR_ISSUING_REFRESH_TOKEN = 'ERROR_ISSUING_REFRESH_TOKEN';
    const BACKUP_CODES_ALREADY_PRESENT = 'BACKUP_CODES_ALREADY_PRESENT';
    const VALIDATION_FAILED = 'VALIDATION_FAILED';
    const AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';
    const RESOURCE_ALREADY_EXISTS = 'RESOURCE_ALREADY_EXISTS';
    const ACTION_NOT_SUPPORTED = 'ACTION_NOT_SUPPORTED';
    const REQUEST_FAILED = 'REQUEST_FAILED';
    const MULTI_FACTOR_ALREADY_ENABLED = 'MULTI_FACTOR_ALREADY_ENABLED';
    const MULTI_FACTOR_NOT_ENABLED = 'MULTI_FACTOR_NOT_ENABLED';
    const MULTI_FACTOR_PARAMETERS_MISSING = 'MULTI_FACTOR_PARAMETERS_MISSING';
    const MULTI_FACTOR_VERIFICATION_FAILED = 'MULTI_FACTOR_VERIFICATION_FAILED';
    const METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';

    //Search
    const SEARCH_RESOURCE_MISMATCH = 'SEARCH_RESOURCE_MISMATCH';
    const SEARCH_ID_INVALID_OR_EXPIRED = 'SEARCH_ID_INVALID_OR_EXPIRED';
}