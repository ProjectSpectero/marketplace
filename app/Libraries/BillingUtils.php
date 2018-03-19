<?php


namespace App\Libraries;


use App\Constants\Currency;
use App\Constants\Errors;
use App\Constants\InvoiceStatus;
use App\Constants\OrderResourceType;
use App\Constants\OrderStatus;
use App\Constants\PaymentType;
use App\Constants\ResponseType;
use App\Constants\UserMetaKeys;
use App\Errors\UserFriendlyException;
use App\Invoice;
use App\Node;
use App\NodeGroup;
use App\Order;
use App\User;
use App\UserMeta;
use Carbon\Carbon;
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
     * @param Carbon $dueNext
     * @return Invoice
     * @throws \Throwable
     */
    public static function createInvoice (Order $order, Carbon $dueNext) : Invoice
    {
        $invoice = new Invoice();
        $invoice->order_id = $order->id;
        $invoice->user_id = $order->user_id;


        $amount = static::getOrderDueAmount($order);
        $tax = TaxationManager::getTaxAmount($order, $amount);
        $amount += $tax;

        $invoice->amount = $amount;
        $invoice->tax = $tax;
        $invoice->status = InvoiceStatus::UNPAID;
        $invoice->due_date = $dueNext;

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
        $amount = $invoice->amount - $invoice->transactions
                ->where('type', PaymentType::CREDIT)
                ->sum('amount');

        return $amount;
    }

    public static function cancelOrder (Order $order)
    {
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
}