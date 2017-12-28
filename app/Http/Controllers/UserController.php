<?php

namespace App\Http\Controllers;

use App\Constants\Messages;
use App\User;
use App\Repositories\UserRepository;
use App\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends V1Controller
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function doCreate(User $user)
    {
        dd($user);
    }

    public function doEdit(User $user)
    {
        // TODO: Implement doEdit() method.
    }

    public function doDelete(User $user)
    {
        // TODO: Implement doDelete() method.
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'post_code' => 'sometimes|required|integer',
            'phone_no' => 'sometimes|required'
        ]);

        $input = $request->all();
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email']
        ]);
        $user->password = \Hash::make($input['password']);
        $user->saveOrFail();

        // Remove the ones that go into the original model
        unset($input['name'], $input['email'], $input['password']);

        foreach ($input as $key => $value)
            UserMeta::addOrUpdateMeta($user, $key, $value);

        return $this->respond($user, null, Messages::USER_CREATED, 201);
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
