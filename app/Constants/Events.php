<?php


namespace App\Constants;

class Events extends Holder
{
    const USER_CREATED = 'user.created';
    const USER_UPDATED = 'user.updated';
    const USER_DELETED = 'user.deleted';
    const USER_PASSWORD_UPDATED = 'user.password.updated';

    const NODE_CREATED = 'node.created';
    const NODE_UPDATED = 'node.updated';
    const NODE_DELETED = 'node.deleted';
    const NODE_UNREACHABLE = 'node.unreachable';
    const NODE_REACHABLE = 'node.reachable';
    const NODE_CONFIG_INVALID = 'node.config.invalid';
    const NODE_VERIFICATION_FAILED = 'node.verification.failed';
    const NODE_REVERIFY = 'node.verification.retry';


    const BILLING_TRANSACTION_ADDED = 'billing.txn.added';
    const ORDER_CREATED = 'billing.order.created';
}