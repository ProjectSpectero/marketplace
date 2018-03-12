<?php

namespace App\Constants;

class Errors extends Holder
{
    const SECRET_IS_REQUIRED = 'SECRET_IS_REQUIRED';
    const ERROR_ISSUING_REFRESH_TOKEN = 'ERROR_ISSUING_REFRESH_TOKEN';
    const BACKUP_CODES_ALREADY_PRESENT = 'BACKUP_CODES_ALREADY_PRESENT';
    const VALIDATION_FAILED = 'VALIDATION_FAILED';
    const AUTHENTICATION_FAILED = 'AUTHENTICATION_FAILED';
    const RESOURCE_ALREADY_EXISTS = 'RESOURCE_ALREADY_EXISTS';
    const ACTION_NOT_SUPPORTED = 'ACTION_NOT_SUPPORTED';
    const REQUEST_FAILED = 'REQUEST_FAILED';
    const MULTI_FACTOR_ALREADY_ENABLED = 'MULTI_FACTOR_ALREADY_ENABLED';
    const MULTI_FACTOR_NOT_ENABLED = 'MULTI_FACTOR_NOT_ENABLED';
    const MULTI_FACTOR_PARAMETERS_MISSING = 'MULTI_FACTOR_PARAMETERS_MISSING';
    const MULTI_FACTOR_VERIFICATION_FAILED = 'MULTI_FACTOR_VERIFICATION_FAILED';
    const USER_VERIFICATION_FAILED = 'USER_VERIFICATION_FAILED';
    const USER_ALREADY_VERIFIED = 'USER_ALREADY_VERIFIED';
    const METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    const RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    const EMAIL_VERIFICATION_NEEDED = 'EMAIL_VERIFICATION_NEEDED';
    const ACCOUNT_DISABLED = 'ACCOUNT_DISABLED';
    const AUTHENTICATION_NOT_ALLOWED = 'AUTHENTICATION_NOT_ALLOWED';
    const UNAUTHORIZED = 'UNAUTHORIZED';
    const COULD_NOT_DUMP_CONFIG = 'COULD_NOT_DUMP_CONFIG';
    const INVALID_NODE_KEY = 'INVALID_NODE_KEY';
    const IDENTITY_MISMATCH = 'IDENTITY_MISMATCH';
    const CURRENT_PASSWORD_MISMATCH = 'CURRENT_PASSWORD_MISMATCH';

    // Search
    const SEARCH_RESOURCE_MISMATCH = 'SEARCH_RESOURCE_MISMATCH';
    const SEARCH_ID_INVALID_OR_EXPIRED = 'SEARCH_ID_INVALID_OR_EXPIRED';

    // Pagination
    const REQUESTED_PAGE_DOES_NOT_EXIST = 'REQUESTED_PAGE_DOES_NOT_EXIST';

    // Node+Service related
    const COULD_NOT_ACCESS_NODE = 'COULD_NOT_ACCESS_NODE';
    const UNKNOWN_SERVICE = 'UNKNOWN_SERVICE';
    const UNKNOWN_ACTION = 'UNKNOWN_ACTION';
    const ACCESS_LEVEL_INSUFFICIENT = 'ACCESS_LEVEL_INSUFFICIENT';
    const NODE_IDENTITY_MISMATCH = 'NODE_IDENTITY_MISMATCH';

    const NODE_PENDING_VERIFICATION = 'NODE_PENDING_VERIFICATION';
    const NODE_ALREADY_VERIFIED = 'NODE_ALREADY_VERIFIED';
    const NODE_BELONGS_TO_GROUP = 'NODE_BELONGS_TO_GROUP';

    const RESOURCE_UNLISTED = 'RESOURCE_UNLISTED';
    const RESOURCE_SOLD_OUT = 'RESOURCE_SOLD_OUT';

    // Invoice+Billing related

    const BILLING_PROFILE_INCOMPLETE = 'BILLING_PROFILE_INCOMPLETE';
    const BILLING_AGREEMENT_NOT_ACCEPTED = 'BILLING_AGREEMENT_NOT_ACCEPTED';
    const INCOMPLETE_PAYMENT = 'INCOMPLETE_PAYMENT';
    const INVOICE_ALREADY_PAID = 'INVOICE_ALREADY_PAID';
    const INVOICE_DUE_IS_LOWER_THAN_LOWEST_THRESHOLD = 'INVOIDE_DUE_IS_LOWER_THAN_LOWEST_THRESHOLD';
    const REFUND_AMOUNT_IS_BIGGER_THAN_TRANSACTION = 'REFUND_AMMOUNT_IS_BIGGER_THAN_TRANSACTION';
    const INVOICE_CURRENCY_MISMATCH = 'INVOICE_CURRENCY_MISMATCH';
    const COULD_NOT_RESOLVE_PAYMENT_PROCESSOR = 'COULD_NOT_RESOLVE_PAYMENT_PROCESSOR';
    const COULD_NOT_REFUND_NON_CREDIT_TXN = 'COULD_NOT_REFUND_NON_CREDIT_TXN';
    const SERVICE_OVERDUE = 'SERVICE_OVERDUE';
    const ORDERS_EXIST = 'ORDERS_EXIST';
    const HAS_NODES = 'HAS_NODES';
    const HAS_ACTIVE_ORDERS = 'HAS_ACTIVE_ORDERS';
    const ORDER_NOT_ACTIVE_YET = 'ORDER_NOT_ACTIVE_YET';
    const ORDER_CONTAINS_UNAVAILABLE_RESOURCE = 'ORDER_CONTAINS_UNAVAILABLE_RESOURCE';
    const GATEWAY_DISABLED_FOR_PURPOSE = 'GATEWAY_DISABLED_FOR_PURPOSE';
    const ZERO_CREDIT_BALANCE = 'ZERO_CREDIT_BALANCE';

    // Billing and payment processing
    const PAYMENT_FAILED = 'PAYMENT_FAILED';

    // Paypal related

    // Stripe related
    const INVALID_STRIPE_TOKEN = 'INVALID_STRIPE_TOKEN';
    const NO_STORED_CARD = 'NO_STORED_CARD';

    // Validation
    const FIELD_REQUIRED = 'FIELD_REQUIRED';
    const FIELD_UNIQUE = 'FIELD_UNIQUE';
    const FIELD_MAXLENGTH = 'FIELD_MAXLENGTH';
    const FIELD_MINLENGTH = 'FIELD_MINLENGTH';
    const FIELD_MINVALUE = 'FIELD_MINVALUE';
    const FIELD_MAXVALUE = 'FIELD_MAXVALUE';
    const FIELD_EMAIL = 'FIELD_EMAIL';
    const FIELD_ALPHANUM = 'FIELD_ALPHANUM';
    const FIELD_ALPHADASH = 'FIELD_ALPHADASH';
    const FIELD_COUNTRY = 'FIELD_COUNTRY';
    const FIELD_BOOLEAN = 'FIELD_BOOLEAN';
    const FIELD_IN = 'FIELD_IN';
    const FIELD_BETWEEN = 'FIELD_BETWEEN';
    const FIELD_EQUALS = 'FIELD_EQUALS';
    const FIELD_REGEX = 'FIELD_REGEX';
    const FIELD_INVALID = 'FIELD_INVALID';

    const IP_ADDRESS_NOT_FOUND = 'IP_ADDRESS_NOT_FOUND';
    const PROMO_CODE_ALREADY_USED = 'PROMO_CODE_ALREADY_USED';
    const PROMO_CODE_LIMIT_REACHED = 'PROMO_CODE_LIMIT_REACHED';
    const PROMO_GROUP_LIMIT_REACHED = 'PROMO_GROUP_LIMIT_REACHED';

    const PROMO_ACTIVATION_LIMIT_REACHED = 'PROMO_ACTIVATION_LIMIT_REACHED';
    const PROMO_CODE_INVALID = 'PROMO_CODE_INVALID';
    const UNPAID_CREDIT_INVOICES_ARE_PRESENT = 'UNPAID_CREDIT_INVOICES_ARE_PRESENT';
}