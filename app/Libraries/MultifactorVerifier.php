<?php


namespace App\Libraries;


use App\BackupCode;
use App\Constants\UserMetaKeys;
use App\User;
use App\UserMeta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
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
        $firstTime = false;

        $multifactorEnabled = null;
        $userSecret = null;
        try
        {
            // The order here matters, since secret MAY sometimes exist, but tfa.enabled may not (first-time cases)
            $userSecret = UserMeta::where(['user_id' => $user->id, 'meta_key' => UserMetaKeys::TwoFactorSecretKey])->firstOrFail();
            $multifactorEnabled = UserMeta::where(['user_id' => $user->id, 'meta_key' => UserMetaKeys::TwoFactorEnabled])->firstOrFail();
        }
        catch (ModelNotFoundException $silenced)
        {
            // If both are null, user does NOT have two-factor turned on. We can say we succeeded.
            if ($userSecret == null && $multifactorEnabled == null)
                return true;

            // If only tfa.enabled is empty but a secret exists, that means it's the user's first time verifying multifactor
            // Any other combination means that required details to verify are not available, and we consider the user
            // to have failed multifactor verification
            if ($multifactorEnabled == null && is_object($userSecret))
                $firstTime = true;
            else
                return false;
        }

        if (!$firstTime)
        {
            // Flow: exists and matches (backupcode) ? success : verify TOTP
            // Backup code verification is NOT available if it is the first time, they MUST use TOTP
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
        }


        if (! $authenticationSucceeded)
        {
            // Backupcodes weren't used, let's go verify TOTP
            $userSecret = $userSecret->meta_value;

            /** @var Google2FA $verifier */
            $verifier = static::initializeTwoFactor();
            try
            {
                if ($verifier->verifyKey($userSecret, $totpToken))
                {
                    // TOTP valid, provide auth data.
                    $authenticationSucceeded = true;
                    if ($firstTime)
                        UserMeta::addOrUpdateMeta($user, UserMetaKeys::TwoFactorEnabled, true);
                }
            }
            catch (InvalidCharactersException $silenced)
            {
                // Mostly happens if the key is invalid, shouldn't be unless it was manually input
            }
        }

        return $authenticationSucceeded;
    }

}