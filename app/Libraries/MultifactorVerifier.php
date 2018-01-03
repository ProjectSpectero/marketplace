<?php


namespace App\Libraries;


use App\BackupCode;
use App\Constants\UserMetaKeys;
use App\User;
use App\UserMeta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PragmaRX\Google2FA\Google2FA;

class MultifactorVerifier
{
    private static $googleTwoFactor;
    private static function initializeTwoFactor ()
    {
        if (static::$googleTwoFactor != null)
            return static::$googleTwoFactor;

        static::$googleTwoFactor = new Google2FA();
        return static::$googleTwoFactor;
    }

    public static function verify (User $user, String $totpToken) : bool
    {
        $authenticationSucceeded = false;

        try
        {
            $userSecret = UserMeta::where(['user_id' => $user->id, 'meta_key' => UserMetaKeys::TwoFactorSecretKey])->firstOrFail();
        }
        catch (ModelNotFoundException $silenced)
        {
            return false;
        }

        // Flow: exists and matches (backupcode) ? success : verify TOTP
        foreach ($user->backupCodes as $backupCode)
        {
            /** @var BackupCode $backupCode */
            if ($backupCode->code === $totpToken)
            {
                $backupCode->delete();
                $authenticationSucceeded = true;
                break;
            }
        }

        if (! $authenticationSucceeded)
        {
            // Backupcodes weren't used, let's go verify TOTP
            $userSecret = $userSecret->meta_value;

            /** @var Google2FA $verifier */
            $verifier = static::initializeTwoFactor();
            if ($verifier->verifyKey($userSecret, $totpToken))
            {
                // TOTP valid, provide auth data.
                $authenticationSucceeded = true;
            }
        }

        return $authenticationSucceeded;
    }

}