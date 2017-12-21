<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use App\Constants\UserMetaKeys;
use App\Constants\Errors;
use App\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Constants\Messages;
use Illuminate\Support\Facades\Auth;

class AuthController extends ApiController
{
    private $userRepository;

    /**
     * AuthController constructor.
     * @param $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
            'post_code' => 'sometimes|required|integer',
            'phone_no' => 'sometimes|required'
        ]);

        $register = $validator->fails() ? 'Error creating user' : $this->userRepository->userCreate($request->all());

        return $this->unifiedResponse(
            $validator->errors(),
            $register, 
            Messages::USER_CREATED
        );    
    }

    public function auth(Request $request)
    {
        $email = $request->get('username');
        $password = $request->get('password');

        $validator = Validator::make($request->all(), [
            'username' => 'required|email',
            'password' => 'required'
        ]);

        $login = $this->userRepository->attemptLogin($email, $password);

        return $this->unifiedResponse(
            $validator->errors(),
            $login,
            Messages::OAUTH_TOKEN_ISSUED
        );

    }

   
    public function verify(Request $request)
    {
        $secret = $request->get('secret');

        $user = Auth::guard('api')->user();
        $errors = array();
        if (empty($secret)) {
            $errors = [
                Errors::SECRET_IS_REQUIRED
            ];
        }

        $verifyUser = $this->userRepository->verifyUser($user, $secret);

        return $this->unifiedResponse(
            $errors,
            $verifyUser,
            Messages::VERIY_SECRET_KEY
        );
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->get('refresh_token');
        $errors = array();
        if (!$refreshToken['success']) {
            $errors = [
                Errors::ERROR_ISSUING_REFRESH_TOKEN
            ];
        }

        return $this->unifiedResponse(
            $errors,
            $this->userRepository->refreshToken($refreshToken),
            Messages::REFRESH_TOKEN_ISSUED
        );
    }


}
