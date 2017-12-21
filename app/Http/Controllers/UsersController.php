<?php

namespace App\Http\Controllers;

use App\Constants\Messages;
use App\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends ApiController
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

   /**
     * When a user enables TFA the keygen method
     * is called to generate a secret key
     */

    public function keygen(Request $request)
    {
        $user = Auth::guard('api')->user();       
        
        $secretKey = $this->userRepository->generateSecretKey($user);

        if (!is_null($secretKey['errors'])) {
            $errors = $secretKey['errors'];
            unset($secretKey['errors']);
        } else {
            $errors = array();
        }
         
        return $this->unifiedResponse(
            $errors,
            $secretKey,
            Messages::SECRET_KEY_GENERATED
        );
    }

    public function regenerateBackupCodes(Request $request)
    {
        $user = Auth::guard('api')->user();
        // TODO: Error handling
        $errors = array();

        $regenCodes = $this->userRepository->regenKeys($user);

        return $this->unifiedResponse(
            $errors,
            $regenCodes,
            Messages::BACKUP_CODES_REGENERATED
        );        
    } 

}
