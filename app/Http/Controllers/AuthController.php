<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
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

        $register = $validator->fails() ? 'Erro creating user' : $this->userRepository->userCreate($request->all());

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
            Messages::OAUTH_TOKKEN_ISSUED
        );

    }

    /**
     * When a user enables TFA the keygen method
     * is called to generate a secret key
     */

    public function keygen(Request $request)
    {
        $user = Auth::guard('api')->user();        
        
        $secretKey = $this->userRepository->generateSecretKey($user);
        
        return $this->unifiedResponse(
            'testError',
            $secretKey,
            'test message'
        );
    }

    public function verify(Request $request)
    {
        $secret = $request->get('secret');

        $user = Auth::guard('api')->user();

        $verifyUser = $this->userRepository->verifyUser($user, $secret);

        return $this->unifiedResponse(
            'testError',
            $verifyUser,
            'testMessage'
        );
    }

    public function refreshToken(Request $request)
    {
        $refreshToken = $request->get('refresh_token');

        return $this->unifiedResponse(
            'testError',
            $this->userRepository->refreshToken($refreshToken),
            'test Message'
        );
    }


}
