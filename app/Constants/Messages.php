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

    const PASSWORD_RESET_TOKEN_ISSUED = 'PASSWORD_RESET_TOKEN_ISSUED';

    const PASSWORD_RESET_SUCCESS = 'PASSWORD_RESET_SUCCESS';
    const BACKUP_CODES_REGENERATED = 'BACKUP_CODES_REGENERATED';
    const MULTI_FACTOR_VERIFICATION_NEEDED = 'MULTI_FACTOR_VERIFICATION_NEEDED';
    const MULTI_FACTOR_FIRSTTIME_VERIFICATION_NEEDED = 'MULTI_FACTOR_FIRSTTIME_VERIFICATION_NEEDED';
    const MULTI_FACTOR_ENABLED = 'MULTI_FACTOR_ENABLED';
    const MULTI_FACTOR_DISABLED = 'MULTI_FACTOR_DISABLED';
    const RESOURCE_ALREADY_EXISTS_ON_OWN_ACCOUNT = 'RESOURCE_ALREADY_EXISTS_ON_OWN_ACCOUNT';

    const NODE_VERIFICATION_QUEUED = 'NODE_VERIFICATION_QUEUED';

    const INVOICE_CREATED = 'INVOICE_CREATED';
    const INVOICE_UPDATED = 'INVOICE_UPDATED';
    const INVOICE_DELETED = 'INVOICE_DELETED';
    const INVOICE_PROCESSED = 'INVOICE_PROCESSED';
    const ORDER_CREATED = 'ORDER_CREATED';
    const ORDER_UPDATED = 'ORDER_UPDATED';
    const ORDER_DELETED = 'ORDER_DELETED';

    const NODE_GROUP_UPDATED = 'NODE_GROUP_UPDATED';
    const NODE_GROUP_DELETED = 'NODE_GROUP_DELETED';
    const NODE_ASSIGNED = 'NODE_ASSIGNED';

    const PAYMENT_PROCESSED = 'PAYMENT_PROCESSED';
    const PAYMENT_TOKEN_PROCESSED = 'PAYMENT_TOKEN_PROCESSED';
    const REFUND_ISSUED = 'REFUND_ISSUED';
    const PAYMENT_PROCESSOR_NOT_ENABLED = 'PAYMENT_PROCESSOR_NOT_ENABLED';
    const SAVED_DATA_CLEARED = 'SAVED_DATA_CLEARED';
}
