<?php

namespace App\Http\Controllers;

use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Errors\NotSupportedException;
use App\Errors\UserFriendlyException;
use App\User;
use App\UserMeta;
use App\Constants\Messages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends CRUDController
{
    public function doEdit(Request $user) : JsonResponse
    {
        throw new NotSupportedException();
        // TODO: Implement doEdit() method.
    }

    public function doDelete(int $id) : JsonResponse
    {
        throw new NotSupportedException();
        // TODO: Implement doDelete() method.
    }

    public function viewOne (int $id) : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function viewAll () : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function doCreate(Request $request) : JsonResponse
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required',
            'post_code' => 'sometimes|required|integer',
            'phone_no' => 'sometimes|required'
        ]);

        $input = $request->all();

        if (User::where('email', $input['email'])->exists())
            throw new UserFriendlyException(Errors::RESOURCE_ALREADY_EXISTS, ResponseType::CONFLICT);

        /** @var User $user */
        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email']
        ]);
        $user->password = Hash::make($input['password']);
        $user->saveOrFail();

        // Remove the ones that go into the original model
        unset($input['name'], $input['email'], $input['password']);

        foreach ($input as $key => $value)
            UserMeta::addOrUpdateMeta($user, $key, $value);

        return $this->respond($user->toArray(), [], Messages::USER_CREATED, ResponseType::CREATED);
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
