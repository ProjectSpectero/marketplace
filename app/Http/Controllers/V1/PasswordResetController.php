<?php

namespace App\Http\Controllers;

use App\Http\Controllers\V1\V1Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PasswordResetController extends V1Controller
{
    // This is the one that looks up an email address and emails them a reset link
    public function generateToken (Request $request) : JsonResponse
    {
        // TODO: implement this, remember to prevent user enumeration. At no point should we confirm or deny a specific email/user exists
        // Just say that IF a user exists with the email address you've shared, they'll receive an email.
        // Create a PasswordResetToken object accordingly, set expiry to now + env('PASSWORD_RESET_TOKEN_EXPIRY')
    }

    // This is the one that emailed link corresponds to.
    public function callback (Request $request, String $token) : JsonResponse
    {
        // TODO: implement this
    }
}
