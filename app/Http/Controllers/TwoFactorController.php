<?php


namespace App\Http\Controllers;


class TwoFactorController extends V1Controller
{
    /**
     * Generate a Google2FA secret key and backup codes
     *
     */

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

    public function verifyUser($user, $secret)
    {
        $google2fa = new Google2FA();

        $backupCodes = $user->backupCodes;

        foreach ($backupCodes as $code) {
            if ($secret == $code->code) {
                $code->delete();
                return true;
            }
        }

        $valid = $google2fa->verifyKey(
            UserMeta::loadMeta($user, UserMetaKeys::SecretKey)->first()->meta_value, $secret
        );

        if ($valid && UserMeta::loadMeta($user, UserMetaKeys::hasTfaOn) == 'false') {
            UserMetaRepository::addMeta($user, UserMetaKeys::hasTfaOn, 'true');
        }

        return $valid;
    }

}