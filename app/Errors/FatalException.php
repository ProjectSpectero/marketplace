<?php


namespace App\Errors;


use Throwable;

class FatalException extends BaseException
{
    public function __construct(string $message = '', int $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}