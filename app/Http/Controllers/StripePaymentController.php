<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;

class StripePaymentController extends Controller
{
    public function addCard (Request $request, PaymentService $paymentService) {
        
        $this->validate($request, [
            "name" => "nullable",
            "card_number" => "required",
            "cvc" => "required",
            "month" => "required",
            "year" => "required"
        ]);
        
        $user = User::select('id','customer_id')->find(auth()->id());
        
        $customerId = $user->customer_id;
        if ($customerId == null) {
            $user->customer_id = $paymentService->createCustomer()?->id;
            $user->save();

            $customerId = $user->customer_id;
        }

        $token = $paymentService->generateCardToken($request->only(['name','card_number','month', 'year', 'cvc']));

        $paymentService->assignCardToCustomer($customerId, $token->id);

        return commonSuccessMessage('Card added successfully.');
    }

    public function getCards (PaymentService $paymentService) {

        $user = User::select('id','customer_id')->find(auth()->id());
        
        $customerId = $user->customer_id;
        if ($customerId == null) {
            
            $user->customer_id = $paymentService->createCustomer()?->id;
            $user->save();

            $customerId = $user->customer_id;
        }

        return apiSuccessMessage("Cards", $paymentService->getAllCards($customerId));
    }

    public function makeCardDefault (Request $request, PaymentService $paymentService) {
        $this->validate($request, [
            'card_id' => 'required'
        ]);

        $user = User::select('id','customer_id')->find(auth()->id());
        
        $customerId = $user->customer_id;

        $paymentService->setCardToDefault($customerId, $request->card_id);

        return commonSuccessMessage("Card set to default");
    }

    public function deleteCard (Request $request , PaymentService $paymentService) {
        $this->validate($request, [
            'card_id' => 'required'
        ]);

        $user = User::select('id','customer_id')->find(auth()->id());
        
        $customerId = $user->customer_id;

        $paymentService->deleteCard($customerId, $request->card_id);

        return commonSuccessMessage("Card deleted successfully.");

    }
}
