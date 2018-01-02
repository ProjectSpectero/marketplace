<?php

namespace App\Http\Controllers\V1;

use App\BackupCode;
use App\Constants\Errors;
use App\Constants\Messages;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\NotSupportedException;
use App\PartialAuth;
use App\User;
use App\UserMeta;
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
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */

    public function verifyToken (Request $request) : JsonResponse
    {
        $this->validate($request, [
            "userId" => "required|numeric",
            "twoFactorToken" => "required",
            "generatedToken" => "required"
        ]);

        $userId = $request->get("userId");
        $twoFactorToken = $request->get("twoFactorToken");
        $totpToken = $request->get("generatedToken");
        $firstTime = false;

        try
        {
            $user = User::findOrFail($userId);
            $userSecret = UserMeta::where(['user_id' => $userId, 'meta_key' => UserMetaKeys::TwoFactorSecretKey])->firstOrFail();
            $partialAuth = PartialAuth::where("user_id", $userId)
                ->where("two_factor_token", $twoFactorToken)
                ->firstOrFail();
        }
        catch (ModelNotFoundException $silenced)
        {
            // Have to catch and manually bail, otherwise the 404 generated is a way to enumerate users into their internal IDs.
            // Any one of the 4 calls above failing is an indicator of TFA not being possible.
            return $this->respond(null, [ Errors::AUTHENTICATION_FAILED ], null, ResponseType::FORBIDDEN);
        }

        try
        {
            UserMeta::where(['user_id' => $userId, 'meta_key' => UserMetaKeys::TwoFactorEnabled])->firstOrFail();
        }
        catch (ModelNotFoundException $silenced)
        {
            // If the rest passed (try-catch block above), but this one failed -- that means that this is an user who's JUST turned on TFA.
            // Let's update a flag to eventually update his state if he passes TOTP verification
            $firstTime = true;
        }

        // At this stage, we know that the user exists and actually has TFA turned on.
        // We have all required information for TFA verification. Let's pull up the partial auth entry.
        $authenticationSucceeded = false;

        // Flow: exists and matches (backupcode) ? success : verify TOTP
        $backupCodes = $user->backupCodes;
        foreach ($backupCodes as $backupCode)
        {
            /** @var BackupCode $backupCode */
            if ($backupCode === $totpToken)
            {
                $backupCode->delete();
                $authenticationSucceeded = true;
            }
        }

        if (! $authenticationSucceeded)
        {
            // Backupcodes weren't used, let's go verify TOTP
            $userSecret = $userSecret->meta_value;
            $verifier = $this->initializeTwoFactor();
            if ($verifier->verifyKey($userSecret, $totpToken))
            {
                // TOTP valid, provide auth data.
                $authenticationSucceeded = true;
                // First time verification succeeded, let's mark user as having TFA enabled.
                if ($firstTime)
                    UserMeta::addOrUpdateMeta($user, UserMetaKeys::TwoFactorEnabled, true);
            }
        }

        if ($authenticationSucceeded)
            $this->respond(\json_decode($partialAuth->data, true), [], Messages::OAUTH_TOKEN_ISSUED);

        return $this->respond(null, [ Errors::AUTHENTICATION_FAILED ], null, ResponseType::FORBIDDEN);
    }

    public function enableTwoFactor (Request $request) : JsonResponse
    {
        throw new NotSupportedException();
    }

    public function disableTwoFactor (Request $request) : JsonResponse
    {
        throw new NotSupportedException();
    }


    public function generateSecretKey($user)
    {
        $google2fa = new Google2FA();
        $secretKey = UserMeta::loadMeta($user, UserMetaKeys::SecretKey);
        $errors = array();
        $backupCodes = new BackupCode();
        if (empty($user->backupCodes->all())) {
            // Generate 5 backup codes
            $backupCodes->generateCodes($user);
        } else {
            $errors = array(
                Errors::BACKUP_CODES_ALREADY_PRESENT
            );
        }

        if (empty($secretKey->first())) {
            $secretKey = $google2fa->generateSecretKey();
            UserMetaRepository::addMeta($user, UserMetaKeys::SecretKey, $secretKey);
        }

        $google2fa_url = $google2fa->getQRCodeGoogleUrl(
            env('COMPANY_NAME'),
            $user->email,
            UserMeta::loadMeta($user, UserMetaKeys::SecretKey)
        );

        return [
            'errors' => $errors,
            'secret_key' => $secretKey->first()->meta_value,
            'qr_code' => $google2fa_url,
            'backup_codes' => BackupCode::where('user_id', $user->id)->pluck('code')
        ];
    }

    /**
     * Invalidate previous backup codes
     * and generate new ones
     */

    public function regenKeys($user)
    {
        foreach($user->backupCodes as $code) {
            $code->delete();
        }

        $backupCodes = new BackupCode();
        $backupCodes->generateCodes($user);

        return [
            'backup_codes' => $user->backupCodes->pluck('code')
        ];
    }

    /**
     * Veirify the user with Google2FA
     *
     */



}