<?php

namespace App\Http\Controllers;

use App\Repositories\UserRepository;
use Illuminate\Http\Request;

class AuthController extends Controller
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

    public function auth(Request $request)
    {
        $email = $request->get('username');
        $password = $request->get('password');

        return response($this->userRepository->attemptLogin($email, $password));
    }


}
