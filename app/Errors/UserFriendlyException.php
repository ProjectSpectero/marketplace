<?php

namespace App\Errors;

/*
 * What's a user friendly exception? It's an exception that can be disclosed to the API consumer.
 */
use Throwable;

class UserFriendlyException extends BaseException
{
    public function __construct(string $message = '', int $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}