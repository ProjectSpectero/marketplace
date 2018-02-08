<?php


namespace App\Libraries\Payment;


use App\Constants\Messages;
use App\Constants\PaymentProcessor;
use App\Constants\UserMetaKeys;
use App\Invoice;
use App\Libraries\Utility;
use App\Models\Opaque\PaymentProcessorResponse;
use App\Order;
use App\Transaction;
use App\UserMeta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Cartalyst\Stripe\Stripe;

class StripeProcessor implements IPaymentProcessor
{

    private $provider;

    /**
     * StripeProcessor constructor.
     */
    public function __construct()
    {
        $this->provider = new Stripe();
    }

    function getName(): string
    {
        return PaymentProcessor::STRIPE;
    }

    function process(Invoice $invoice): PaymentProcessorResponse
    {
        // TODO: Implement process() method.
    }

    function callback(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->get('token');

        try
        {
           $customer =  UserMeta::loadMeta($user, UserMetaKeys::StripeCustomerIdentifier, true);
        }
        catch (ModelNotFoundException $silenced)
        {
            $customer = $this->createCustomer($user, $token);
        }

        return Utility::generateResponse($customer, [], Messages::PAYMENT_PROCESSED);
    }

    function refund(Transaction $transaction, Float $amount): PaymentProcessorResponse
    {
        // TODO: Implement refund() method.
    }

    function subscribe(Order $order): PaymentProcessorResponse
    {
        // TODO: Implement subscribe() method.
    }

    function unSubscribe(Order $order): PaymentProcessorResponse
    {
        // TODO: Implement unSubscribe() method.
    }

    private function createCustomer($user, $token)
    {
        $customer = $this->provider->customers()->create([
            'email' => $user->email,
            'source' => $token
        ]);

        UserMeta::addOrUpdateMeta($user, UserMetaKeys::StripeCustomerIdentifier ,$customer['id']);

        return $customer;
    }

    public function getCallbackRules()
    {
        return [
            'stripeToken' => 'required'
        ];
    }
}