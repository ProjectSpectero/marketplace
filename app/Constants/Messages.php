<?php

namespace App\Constants;

class Messages extends Holder
{
    const OAUTH_TOKEN_ISSUED = 'OAUTH_TOKEN_ISSUED';
    const OAUTH_TOKEN_REFRESHED = 'OAUTH_TOKEN_REFRESHED';
    const USER_CREATED = 'USER_CREATED';
    const USER_VERIFIED = 'USER_VERIFIED';
    const USER_UPDATED = 'USER_UPDATED';
    const USER_DESTROYED = 'USER_DESTROYED';
    const GET_USERS_LIST = 'GET_USERS_LIST';
    const GET_USER = 'GET_USER';
    const SECRET_KEY_GENERATED = 'SECRET_KEY_GENERATED';
    const VERIY_SECRET_KEY = 'VERIY_SECRET_KEY';
    const REFRESH_TOKEN_ISSUED = 'REFRESH_TOKEN_ISSUED';
    const BACKUP_CODES_REGENERATED = 'BACKUP_CODES_REGENERATED';
    const MULTI_FACTOR_VERIFICATION_NEEDED = 'MULTI_FACTOR_VERIFICATION_NEEDED';
    const MULTI_FACTOR_FIRSTTIME_VERIFICATION_NEEDED = 'MULTI_FACTOR_FIRSTTIME_VERIFICATION_NEEDED';
    const MULTI_FACTOR_ENABLED = 'MULTI_FACTOR_ENABLED';
    const MULTI_FACTOR_DISABLED = 'MULTI_FACTOR_DISABLED';
    const RESOURCE_ALREADY_EXISTS_ON_OWN_ACCOUNT = 'RESOURCE_ALREADY_EXISTS_ON_OWN_ACCOUNT';
}
