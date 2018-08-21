<?php

namespace App\Http\Controllers\V1;

use App\BackupCode;
use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Libraries\MultifactorVerifier;
use App\Libraries\Utility;
use App\Models\Opaque\TwoFactorManagementResponse;
use App\PartialAuth;
use App\User;
use App\UserMeta;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends V1Controller
{
    private $googleTwoFactor;
    private function initializeTwoFactor ()
    {
        if ($this->googleTwoFactor != null)
            return $this->googleTwoFactor;

        $this->googleTwoFactor = new Google2FA();
        return $this->googleTwoFactor;

    }

    /**
     * Verify the user provided two factor details to complete authentication
     * This endpoint DOES NOT handle first-time TOTP enablement, that's done by the middleware.
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */

    public function verifyToken (Request $request) : JsonResponse
    {
        $this->validate($request, [
            'userId' => 'required|numeric',
            'twoFactorToken' => 'required',
            'generatedToken' => 'required'
        ]);

        $userId = $request->get('userId');
        $twoFactorToken = $request->get('twoFactorToken');
        $totpToken = $request->get('generatedToken');

        try
        {
            /** @var User $user */
            $user = User::findOrFail($userId);
            $partialAuth = PartialAuth::findByUserIdAndToken($userId, $twoFactorToken)->firstOrFail();
        }
        catch (ModelNotFoundException $silenced)
        {
            // Have to catch and manually bail, otherwise the 404 generated is a way to enumerate users into their internal IDs.
            // Any one of the 4 calls above failing is an indicator of TFA not being possible.
            throw new UserFriendlyException(Errors::AUTHENTICATION_FAILED, ResponseType::FORBIDDEN);
        }

        // At this stage, we know that the user exists and actually has TFA turned on.
        // An assumption has been made here, we assume that if tfa is enabled, their secret exists too.
        // Someone correct me if this (^) is not always true.
        if (MultifactorVerifier::verify($user, $totpToken))
        {
            // Not this user's first time logging in anymore.
            Utility::incrementLoginCount($user);

            $partialAuth->delete();
            return $this->respond(json_decode($partialAuth->data, true), [], Messages::OAUTH_TOKEN_ISSUED);
        }


        return $this->respond(null, [ Errors::AUTHENTICATION_FAILED => '' ], null, ResponseType::FORBIDDEN);
    }



    public function enableTwoFactor (Request $request) : JsonResponse
    {
        $user = $request->user();
        try
        {
            UserMeta::loadMeta($user, UserMetaKeys::TwoFactorEnabled, true);
            UserMeta::loadMeta($user, UserMetaKeys::TwoFactorSecretKey, true);
        }
        catch (ModelNotFoundException $silenced)
        {
            // If this is thrown, we can proceed. It means that the user does NOT have TFA turned on.
            $twoFactorService = $this->initializeTwoFactor();

            // Let's get rid of all old backup codes just in case.
            $this->clearBackupCodes($user);

            // Let us generate the default amount of backup codes for the user
            $generatedBackupCodes = $this->generateBackupCodes($user, env('DEFAULT_BACKUP_CODES_COUNT', 5));

            // Let us generate and persist the user's secret key.
            $secretKey = $twoFactorService->generateSecretKey();
            UserMeta::addOrUpdateMeta($user, UserMetaKeys::TwoFactorSecretKey, $secretKey);

            // Let us generate an URL to the QR code
            // This makes the secret key visible in the URI, however it's fine for now (still HTTPS)
            // TODO: Look into local (secure) QR code generation.
            $twoFactorService->setAllowInsecureCallToGoogleApis(true);
            $qrCodeUrl = $twoFactorService->getQRCodeGoogleUrl(env('COMPANY_NAME', 'smartplace'),
                $user->email,
                $secretKey
            );

            $response = new TwoFactorManagementResponse();
            $response->userId = $user->id;
            $response->backupCodes = $generatedBackupCodes;
            $response->qrCodeUrl = $qrCodeUrl;
            $response->secretCode = $secretKey;

            return $this->respond($response->toArray(), [], Messages::MULTI_FACTOR_FIRSTTIME_VERIFICATION_NEEDED);
        }

        // If not thrown, user has two factor turned on already. Trying to turn it on again does not make sense.
        throw new UserFriendlyException(Errors::MULTI_FACTOR_ALREADY_ENABLED);
    }

    public function disableTwoFactor (Request $request) : JsonResponse
    {
        $user = $request->user();
        try
        {
            $isTwoFactorEnabled = UserMeta::loadMeta($user, UserMetaKeys::TwoFactorEnabled, true);
            $userSecretKey = UserMeta::loadMeta($user, UserMetaKeys::TwoFactorSecretKey, true);

            $isTwoFactorEnabled->delete();
            $userSecretKey->delete();

            $this->clearBackupCodes($user);

            return $this->respond(null, [], Messages::MULTI_FACTOR_DISABLED);
        }
        catch (ModelNotFoundException $silenced)
        {
            // If these two don't exist, that means TFA was NOT turned on.
            throw new UserFriendlyException(Errors::MULTI_FACTOR_NOT_ENABLED);
        }
    }

    public function showUserBackupCodes (Request $request) : JsonResponse
    {
        $user = $request->user();

        if (! $this->isMultifactorEnabled($user))
            throw new UserFriendlyException(Errors::MULTI_FACTOR_NOT_ENABLED);

        $codes = $user->backupCodes->pluck('code');
        return $this->respond($codes->toArray());
    }

    public function regenerateUserBackupCodes (Request $request) : JsonResponse
    {
        $user = $request->user();

        if (! $this->isMultifactorEnabled($user))
            throw new UserFriendlyException(Errors::MULTI_FACTOR_NOT_ENABLED);

        // MFA is turned on, let's clear all old codes and generate new ones
        $this->clearBackupCodes($user);
        $codes = $this->generateBackupCodes($user, env('DEFAULT_BACKUP_CODES_COUNT', 5));

        return $this->respond($codes);
    }

    public function firstTimeMultiFactor (Request $request) : JsonResponse
    {
        return $this->respond(null, [], Messages::MULTI_FACTOR_ENABLED);
    }

    private function isMultifactorEnabled (User $user) : bool
    {
        try
        {
            UserMeta::loadMeta($user, UserMetaKeys::TwoFactorEnabled, true);
        }
        catch (ModelNotFoundException $silenced)
        {
            return false;
        }
        return true;
    }

    private function generateBackupCodes(User $user, int $count) : array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++)
        {
            $currentCode = Utility::getRandomString();
            BackupCode::create([
                'user_id' => $user->id,
                'code' => $currentCode
            ]);
            $codes[] = $currentCode;
        }
        return $codes;
    }

    private function clearBackupCodes (User $user) : int
    {
        return $user->backupCodes()->delete();
    }
}