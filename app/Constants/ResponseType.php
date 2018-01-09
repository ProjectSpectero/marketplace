<?php

namespace App\Constants;

class ResponseType extends Holder
{
    const OK = 200;
    const CREATED = 201;
    const NO_CONTENT = 204;
    const BAD_REQUEST = 400;
    const NOT_AUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const CONFLICT = 409;
    const UNPROCESSABLE_ENTITY = 422;
    const INTERNAL_SERVER_ERROR = 500;
}