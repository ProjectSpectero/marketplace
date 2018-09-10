<?php


namespace App\Libraries;


use App\Constants\Authority;
use App\Constants\Currency;
use App\Constants\Errors;
use App\Constants\InvoiceStatus;
use App\Constants\InvoiceType;
use App\Constants\NodeMarketModel;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\PaymentProcessor;
use App\Constants\PaymentProcessorResponseType;
use App\Constants\PaymentType;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Constants\UserRoles;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Libraries\Payment\AccountCreditProcessor;
use App\Libraries\Payment\IPaymentProcessor;
use App\Libraries\Payment\StripeProcessor;
use App\Mail\PaymentRequestMail;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Node;
use App\NodeGroup;
use App\Order;
use App\Transaction;
use App\User;
use App\UserMeta;
use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
            if (empty($user->name))
                throw new ModelNotFoundException(Errors::FIELD_REQUIRED);

            $addrLine1 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineOne, true)->meta_value;

            $city = UserMeta::loadMeta($user, UserMetaKeys::City, true)->meta_value;
            $state = UserMeta::loadMeta($user, UserMetaKeys::State, true)->meta_value;
            $country = UserMeta::loadMeta($user, UserMetaKeys::Country, true)->meta_value;
            $postCode = UserMeta::loadMeta($user, UserMetaKeys::PostCode, true)->meta_value;

            // These are nullable
            $organization = UserMeta::loadMeta($user, UserMetaKeys::Organization);
            $taxId = UserMeta::loadMeta($user, UserMetaKeys::TaxIdentification);
            $addrLine2 = UserMeta::loadMeta($user, UserMetaKeys::AddressLineTwo);

        }
        catch (ModelNotFoundException $e)
        {
            throw new UserFriendlyException(Errors::BILLING_PROFILE_INCOMPLETE, ResponseType::FORBIDDEN);
        }

        return [
            'customer_name' => $user->name,
            UserMetaKeys::AddressLineOne => $addrLine1,
            UserMetaKeys::AddressLineTwo => ($addrLine2 instanceof Builder  || $addrLine2 == null) ? null : $addrLine2->meta_value,
            UserMetaKeys::City => $city,
            UserMetaKeys::State => $state,
            UserMetaKeys::Country => $country,
            UserMetaKeys::PostCode => $postCode,
            UserMetaKeys::Organization => ($organization instanceof Builder || $organization == null) ? null : $organization->meta_value,
            UserMetaKeys::TaxIdentification => ($taxId instanceof Builder || $taxId == null) ? null : $taxId->meta_value
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

    /**
     * @param Order $order
     * @return float
     */
    public static function getOrderDueAmount (Order $order) : float
    {
        // Let's figure out the amount.
        $items = $order->lineItems
            ->where('status', '!=', OrderStatus::CANCELLED);

        $amount = 0.0;
        foreach ($items as $item)
            $amount += $item->quantity * $item->amount;

        return $amount;
    }

    /**
     * @param Order $order
     * @param Carbon $dueDate
     * @return Invoice
     * @throws \Throwable
     */
    public static function createInvoice (Order $order, Carbon $dueDate) : Invoice
    {
        $invoice = new Invoice();
        $invoice->order_id = $order->id;
        $invoice->user_id = $order->user_id;

        $amount = static::getOrderDueAmount($order);
        $tax = TaxationManager::getTaxAmount($order, $amount);
        $amount += $tax;

        $invoice->type = InvoiceType::STANDARD;
        $invoice->amount = $amount;
        $invoice->tax = $tax;
        $invoice->status = InvoiceStatus::UNPAID;
        $invoice->due_date = $dueDate;
        $invoice->last_reminder_sent = Carbon::now();

        // TODO: Default into USD for now, we'll fix this later
        $invoice->currency = Currency::USD;

        $invoice->saveOrFail();

        $order->last_invoice_id = $invoice->id;
        $order->saveOrFail();

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     * @return mixed
     */
    public static function getInvoiceDueAmount (Invoice $invoice)
    {
        if ($invoice->status == InvoiceStatus::CANCELLED)
            return 0;

        $existingAmount = Transaction::where('invoice_id', $invoice->id)
            ->where('type', PaymentType::CREDIT)
            ->sum('amount');

        $amount = $invoice->amount - $existingAmount;

        return $amount;
    }

    public static function cancelOrder (Order $order)
    {
        // TODO: Build support for full ent handling, and at that point enable cancellations.
        if ($order->isEnterprise())
            throw new UserFriendlyException(Errors::CONTACT_ACCOUNT_REPRESENTATIVE, ResponseType::FORBIDDEN);

        foreach ($order->lineItems as $lineItem)
        {
            $lineItem->status = OrderStatus::CANCELLED;
            $lineItem->saveOrFail();
        }

        if ($order->lastInvoice != null)
        {
            $lastInvoice = $order->lastInvoice;
            if ($lastInvoice->status != InvoiceStatus::PAID)
            {
                $lastInvoice->status = InvoiceStatus::CANCELLED;
                $lastInvoice->saveOrFail();
            }
        }

        $order->status = OrderStatus::CANCELLED;
        $order->saveOrFail();
    }

    public static function verifyOrder (Order $order, bool $throwsExceptions = true) : array
    {
        $errors = [];

        foreach ($order->lineItems as $item)
        {
            $isGroup = false;

            switch ($item->type)
            {
                case OrderResourceType::NODE:
                    $resource = Node::find($item->resource);
                    break;
                case OrderResourceType::NODE_GROUP:
                    $resource = NodeGroup::find($item->resource);
                    $isGroup = true;
                    break;
                // TODO: Add proper handling for enterprise, and at that point get a proper verification routine going.
                case OrderResourceType::ENTERPRISE:
                    // Why 2? Because switch acts like a loop in PHP (yeah...)
                    continue 2;
                default:
                    $resource = null;
            }

            if ($resource == null)
            {
                if ($throwsExceptions)
                    throw new UserFriendlyException(Errors::RESOURCE_NOT_FOUND);

                $errors[] = [
                    'id' => $item->id,
                    'reason' => Errors::RESOURCE_NOT_FOUND
                ];

                continue;
            }

            if ($isGroup && ! $resource->hasMarketEligibleNodes())
            {
                if ($throwsExceptions)
                    throw new UserFriendlyException(Errors::RESOURCE_EMPTY, ResponseType::FORBIDDEN);

                $errors[] = [
                    'id' => $item->id,
                    'reason' => Errors::RESOURCE_EMPTY
                ];
            }

            switch ($resource->market_model)
            {
                case NodeMarketModel::UNLISTED:
                    if ($throwsExceptions)
                        throw new UserFriendlyException(Errors::RESOURCE_NOT_FOUND);

                    $errors[] = [
                        'id' => $item->id,
                        'reason' => Errors::RESOURCE_NOT_FOUND // Purposefully hidden with a falsified error message.
                    ];

                    break;

                case NodeMarketModel::LISTED_DEDICATED:
                    $existingOrders = $resource->getOrders(OrderStatus::ACTIVE)->get();
                    $existingCount = count($existingOrders);

                    if ($existingCount == 1 && $order->status == OrderStatus::ACTIVE)
                    {
                        // It might be THIS order, we need to verify that for already active orders.
                        $resourceOrder = $existingOrders->first();
                        if ($order->id == $resourceOrder->id)
                            continue;
                    }
                    elseif ($existingCount != 0)
                    {
                        if ($throwsExceptions)
                            throw new UserFriendlyException(Errors::RESOURCE_SOLD_OUT, ResponseType::FORBIDDEN);

                        $errors[] = [
                            'id' => $item->id,
                            'reason' => Errors::RESOURCE_SOLD_OUT
                        ];
                    }

                    break;

                case NodeMarketModel::LISTED_SHARED:
                    // TODO: Enforce the shared limit here once it exists.
                    break;
            }
        }

        return $errors;
    }

    private static function formulateUserPlanCacheKey (User $user)
    {
        return 'core.user.' . $user->id . '.plans';
    }

    public static function getUserPlans (User $user)
    {
        $cacheKey = static::formulateUserPlanCacheKey($user);
        if (Cache::has($cacheKey))
            return Cache::get($cacheKey);

        /** @var array<Order> $orders */
        $orders = Order::findForUser($user->id)
            ->where('status', OrderStatus::ACTIVE)
            ->get();

        $plans = [];

        /** @var Order $order */
        foreach ($orders as $order)
        {
            $currentPlans = $order->plans();
            $plans = array_unique(array_merge($plans, $currentPlans));
        }

        Cache::put($cacheKey, $plans, env('USER_PLANS_CACHE_MINUTES', 1));

        return $plans;
    }

    public static function addTransaction (IPaymentProcessor $processor, Invoice $invoice,
                                    Float $amount, Float $fee,
                                    String $transactionId, String $transactionType,
                                    String $reason, String $rawData,
                                    int $originalTransactionId = -1) : Transaction
    {
        $transaction = new Transaction();
        $transaction->invoice_id = $invoice->id;
        $transaction->payment_processor = $processor->getName();
        $transaction->reference = $transactionId;
        $transaction->type = $transactionType;
        $transaction->reason = $reason;
        $transaction->amount = $amount;
        $transaction->fee = $fee;
        $transaction->currency = $invoice->currency;
        $transaction->raw_response = $rawData;

        if ($originalTransactionId != -1)
            $transaction->original_transaction_id = $originalTransactionId;

        $transaction->saveOrFail();

        return $transaction;
    }

    public static function attemptToChargeIfPossible (Invoice $invoice) : bool
    {
        /** @var User $user */
        $user = $invoice->user;
        $request = new Request();

        // This is mostly a error-case only variable, the two success cases have their own checks, and immediately return instead of using this.
        $success = false;

        if (in_array($invoice->status, [ InvoiceStatus::UNPAID, InvoiceStatus::PARTIALLY_PAID ]))
        {
            try
            {
                $request->setUserResolver(function() use ($user)
                {
                    return $user;
                });

                if ($user->credit > 0
                    && $user->credit_currency == $invoice->currency)
                {
                    \Log::info("$invoice->id has positive balance ($user->credit $user->credit_currency), and currency matches invoice. Attempting to charge $invoice->amount $invoice->currency ...");
                    // OK, he has dollarydoos. Let's go take some.
                    $paymentProcessor = new AccountCreditProcessor($request);
                    $paymentProcessor->enableAutoProcessing();

                    // It is possible for this call to only manage to partially pay an invoice depending on credit availability.
                    $paymentProcessor->process($invoice);
                }

                $postAccountCreditDue = BillingUtils::getInvoiceDueAmount($invoice);

                // We managed to fully charge it from account credit, let's bail. No need to bother with stored card.
                if ($postAccountCreditDue <= 0)
                    return true;

                // Useless call to verify if it's possible to attempt to charge him. If we got here, that means credit(s) was/were not enough.
                // At this stage, the due is > 0 and we will be attempting to charge it to their stored card, should one exist.
                UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier, true);

                // This attempts to charge him every day if it fails. We should probably cap it out at x attempts if the card is a dud.
                // TODO: Implement tracking for non-operational stored payment methods a la ^.

                \Log::info("$invoice->id has an attached user with a saved CC. Attempting to charge $invoice->amount $invoice->currency via Stripe...");

                $paymentProcessor = new StripeProcessor($request);
                $paymentProcessor->enableAutoProcessing();

                /** @var PaymentProcessorResponse $cardProcessingResponse */
                $cardProcessingResponse = $paymentProcessor->process($invoice);

                return $cardProcessingResponse->type == PaymentProcessorResponseType::SUCCESS;
            }
            catch (UserFriendlyException $exception)
            {
                \Log::error("A charge attempt (auto-charge) on invoice #$invoice->id has failed: ", [ 'ctx' => $exception ]);
                // We tried to charge him, but ultimately failed. Let's make him aware of this fact, and fish for payment.
                Mail::to($user->email)->queue(new PaymentRequestMail($invoice, $exception->getMessage()));

                // User did have a saved card (or account credit), but our attempt to fully charge the invoice has nonetheless failed.
                // Redundant assignment, but just in case...
                $success = false;
            }
            catch (ModelNotFoundException $silenced)
            {
                // User did not have a saved card, and hence let's notify the caller that our attempt to fully charge the invoice has failed.
                // Redundant assignment, but just in case...
                $success = false;
            }

            return $success;
        }

        throw new UserFriendlyException(Errors::INVOICE_STATUS_MISMATCH);
    }

    // Smelly code, should make the providers disclose the rules instead of centralizing like this.
    // Every time a provider is added, this place needs maintenance. Bad design, to be fixed later.
    public static function resolveUsableGateways (Invoice $invoice, User $user = null)
    {
        $invoiceUser = $invoice->user;

        $valid = [];

        $providers = PaymentProcessor::getConstants();

        // Flow is that everything is valid by default, unless one or more rules fail.
        // TODO: Integrate fraud-scores in this decision-making process.
        foreach ($providers as $provider)
        {
            $lowerCaseValue = strtolower($provider);

            if ($invoice->type == InvoiceType::CREDIT &&
                ! in_array($provider, PaymentProcessor::getCreditAddAllowedVia()))
                continue;

            switch ($provider)
            {
                case PaymentProcessor::PAYPAL:
                    // Paypal is allowed everywhere for now.
                    if (env('PAYPAL_ENABLED', false) == true)
                        $valid[] = $lowerCaseValue;
                    break;

                case PaymentProcessor::CRYPTO:
                    // Crypto is allowed everywhere for now.
                    if (env('CRYPTO_ENABLED', false) == true)
                        $valid[] = $lowerCaseValue;
                    break;

                case PaymentProcessor::STRIPE:
                    // Stripe is currently allowed for all non-credit payments
                    if (env('STRIPE_ENABLED', false) == true)
                        $valid[] = $lowerCaseValue;

                    break;

                case PaymentProcessor::ACCOUNT_CREDIT:
                    if ($invoiceUser->credit > 0 &&
                        $invoiceUser->credit_currency === $invoice->currency)
                        $valid[] = $lowerCaseValue;

                    break;

                case
                    PaymentProcessor::MANUAL:
                    if ($user != null &&  ($user->can(Authority::ManualPay) || $user->isAn(UserRoles::ADMIN)))
                        $valid[] = $lowerCaseValue;

                    break;
            }

        }

        return $valid;
    }

    // Checks whether the user the invoice belongs to has ample credit (or a stored (and VALID) medium in file) to auto-pay it on due date.
    public static function resolveAutoDeductionMethod(Invoice $invoice)
    {
        $user = $invoice->user;
        $dueAmount = self::getInvoiceDueAmount($invoice);

        $method = null;

        // User's account has enough credit balance to cover this invoice, let's not bother them.
        if ($user->credit >= $dueAmount && $user->credit_currency == $invoice->currency)
            $method = PaymentProcessor::ACCOUNT_CREDIT;

        if ($method == null)
        {
            try
            {
                // User has a stored card, we'll auto-charge it when the time comes.
                // TODO: Implement tracking for non-operational stored payment methods.

                UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier, true);

                $method = PaymentProcessor::STRIPE;
            }
            catch (ModelNotFoundException $silenced)
            {
                // We do nothing, for there is nothing to do.
            }
        }

        return $method;
    }
}