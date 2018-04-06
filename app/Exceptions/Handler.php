<?php

namespace App\Exceptions;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\BaseException;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\Libraries\Environment;
use App\Libraries\Utility;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Validator;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        BaseException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $e
     * @return void
     */
    public function report(Exception $e)
    {
        parent::report($e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $e
     * @return \Illuminate\Http\Response | JsonResponse
     */
    public function render($request, Exception $e)
    {
        // This means the env is NOT production, we're free to throw whatever we feel like.
        if (! Environment::isProduction())
            return parent::render($request, $e);

        $returnCode = $e->getCode() != 0 ? $e->getCode() : 400;
        $message = $e->getMessage();
        $data = method_exists($e, 'getData') ? $e->getData() : '';
        $version = null;

        $matchedRoute = $request->route();
        if (isset($matchedRoute[1]['uses']))
        {
            list ($controller, $method) = explode('@', $matchedRoute[1]['uses']);
            if (class_exists($controller))
            {
                $controller = new $controller();
                if (is_object($controller))
                {
                    if (property_exists($controller, 'version'))
                        $version = $controller->version;
                }
            }
        }

        if ($version == null)
            $version = 'v1';

        // Production error rendering.
        // List of errors we have custom handlers for.
        switch (true)
        {
            case $e instanceof AuthorizationException:
                return Utility::generateResponse(null, [ Errors::UNAUTHORIZED ], Errors::REQUEST_FAILED, $version, ResponseType::FORBIDDEN);
                break;
            case $e instanceof UserFriendlyException:
                //This is an error we can actually disclose to the user
                return Utility::generateResponse(null, [ $message ], Errors::REQUEST_FAILED, $version, $returnCode);
                break;
            case $e instanceof ValidationException:
                $parsedErrors = [];
                foreach ($e->errors() as $field => $messages)
                {
                    foreach ($messages as $message)
                        $parsedErrors[] = $message;
                }

                return Utility::generateResponse(null, [ Errors::VALIDATION_FAILED => $parsedErrors ], Errors::REQUEST_FAILED, $version, ResponseType::UNPROCESSABLE_ENTITY);
                break;
            case $e instanceof MethodNotAllowedHttpException:
                return Utility::generateResponse(null, [ Errors::METHOD_NOT_ALLOWED ], Errors::REQUEST_FAILED, $version, ResponseType::METHOD_NOT_ALLOWED);
                break;

            case $e instanceof NotFoundHttpException:
                return Utility::generateResponse(null, [ Errors::ENDPOINT_NOT_FOUND ], Errors::REQUEST_FAILED, $version, ResponseType::NOT_FOUND);
                break;

            case $e instanceof ModelNotFoundException:
                return Utility::generateResponse(null, [ Errors::RESOURCE_NOT_FOUND ], Errors::REQUEST_FAILED, $version, ResponseType::NOT_FOUND);
                break;
            case $e instanceof NotSupportedException:
                return Utility::generateResponse(null, [ Errors::ACTION_NOT_SUPPORTED ], Errors::REQUEST_FAILED, $version, ResponseType::BAD_REQUEST);
                break;
        }

        // For everything else, something grave has gone wrong. The error should NOT be disclosed to the user, but logged as a part of the handle() routine.
        return Utility::generateResponse(null, [ Errors::REQUEST_FAILED ], Errors::REQUEST_FAILED, $version, ResponseType::INTERNAL_SERVER_ERROR);
    }
}
