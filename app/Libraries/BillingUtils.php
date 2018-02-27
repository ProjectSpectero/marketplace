<?php


namespace App\Libraries;


use App\Constants\Errors;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\User;
use App\UserMeta;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BillingUtils
{
    /**
     * @param User $user
     * @return array
     * @throws UserFriendlyException
     */
    public static function compileDetails (User $user)
    {
        try
        {
            $addrLine1 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineOne, true)->meta_value;
            $addrLine2 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineTwo, true)->meta_value;
            $city = UserMeta::loadMeta($user, UserMetaKeys::City, true)->meta_value;
            $state = UserMeta::loadMeta($user, UserMetaKeys::State, true)->meta_value;
            $country = UserMeta::loadMeta($user, UserMetaKeys::Country, true)->meta_value;
            $postCode = UserMeta::loadMeta($user, UserMetaKeys::PostCode, true)->meta_value;

            // These are nullable
            $organization = UserMeta::loadMeta($user, UserMetaKeys::Organization);
            $taxId = UserMeta::loadMeta($user, UserMetaKeys::TaxIdentification);

        }
        catch (ModelNotFoundException $e)
        {
            throw new UserFriendlyException(Errors::BILLING_PROFILE_INCOMPLETE, ResponseType::FORBIDDEN);
        }

        return [
            'addrLine1' => $addrLine1,
            'addrLine2' => $addrLine2,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'postCode' => $postCode,
            'organization' => $organization,
            'taxId' => $taxId
        ];
    }

    /**
     * @param array $compiledDetails
     * @param User $user
     * @return mixed|string
     * @throws UserFriendlyException
     */
    public static function getFormattedUserAddress ($compiledDetails = [], User $user)
    {
        if (empty($compiledDetails))
            $details = static::compileDetails($user);
        else
            $details = $compiledDetails;

        $formattedUserAddress = $details['addrLine1'];
        if (! empty($details['addrLine2']))
            $formattedUserAddress .= PHP_EOL . $details['addrLine2'];
        $formattedUserAddress .= PHP_EOL . $details['city'] . ', ' . $details['state'] . ', ' . $details['postCode'];
        $formattedUserAddress .= PHP_EOL . $details['country'];

        return $formattedUserAddress;
    }
}