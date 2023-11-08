<?php


namespace App\Services\Payment;
use Stripe\StripeClient;
use Stripe\Exception\CardException;
use App\Exceptions\AppException;
use Exception;

class PaymentService
{


    private $stripe;
    public function __construct()
    {
        $this->stripe = new StripeClient('sk_test_51H0UoCJELxddsoRYdF40WwR8HUvA8U5wgUNqQwDCweZT4TnbAuIGINVtVWAItPMcSoMOighLxdZR1Jjl8vdUwldb00EMPAVgIE');

    }


    public function createCustomer () {
        $customer = $this->stripe->customers->create([
            'description' => 'Medicist Customer',
        ]);
        return $customer;
    }


    public function createExpressAccount () {

        try {
            $express_account = $this->stripe->accounts->create([
                'type' => 'express'
            ]);

            return $express_account;
        }catch(\Stripe\Exception\InvalidRequestException  $e) {
            throw new AppException($e->getError()->message);
        }catch (\Stripe\Exception\CardException $e) {
            throw new AppException($e->getError()->message);
        }
       

    }


    public function generateCardToken ($cardData) {
        
        try {
           
            $token = $this->stripe->tokens->create([
                'card' => [
                    'name' => $cardData['name'],
                    'number' => $cardData['card_number'],
                    'exp_month' => $cardData['month'],
                    'exp_year' => $cardData['year'],
                    'cvc' => $cardData['cvc']
                ]
            ]);
            return $token;
        }
        catch(\Stripe\Exception\InvalidRequestException  $e) {
            throw new AppException($e->getError()->message);
        }
        catch (\Stripe\Exception\CardException $e) {
            throw new AppException($e->getError()->message);
        }
        catch (Exception $e) {
          throw new AppException($e->getMessage(), 400);
        }
        
    }

    public function assignCardToCustomer ($customerId , $cardTokenId) {
        try {
            //code...
            $card = $this->stripe->customers->createSource(
                $customerId,
                ['source' => $cardTokenId]
            );
            return $card;
        } catch (AppException $e) {
            //throw $th;
          throw new AppException($e->getMessage(), 400);

        }
    }

    public function getAllCards ($customerId) {
        try {
            //code...
            $cards = $this->stripe->customers->allSources(
                $customerId
            );
            return $cards;
        } catch (\Stripe\Exception\CardException $e) {
            throw new AppException($e->getError()->message);
        } catch(\Stripe\Exception\InvalidRequestException  $e) {
            throw new AppException($e->getError()->message);
        }
        

    }

    public function setCardToDefault ($customerId, $cardId) {
        try {
            $default = $this->stripe->customers->update(
                $customerId,
                [ 'default_source' => $cardId]
            );
            return $default;
        }catch(\Stripe\Exception\InvalidRequestException  $e) {
            throw new AppException($e->getError()->message);
        }catch (\Stripe\Exception\CardException $e) {
            throw new AppException($e->getError()->message);
        }

    }


    public function deleteCard ($customerId, $cardId) {
        try {
            $deleteCard = $this->stripe->customers->deleteSource(
                $customerId,
                $cardId,
                []
            );
            return $deleteCard;
        }catch(\Stripe\Exception\InvalidRequestException  $e) {
            throw new AppException($e->getError()->message);
        }catch (\Stripe\Exception\CardException $e) {
            throw new AppException($e->getError()->message);
        }

    }

    public  function chargeAmount ($cardId, $customerId,$amount) {
        try {
            $charge = $this->stripe->charges->create([
                'amount' => round(($amount)*100),
                'currency' => 'USD',
                'customer' => $customerId,
                'source' => $cardId,
              ]);
              return $charge;
        }catch(\Stripe\Exception\InvalidRequestException  $e) {
            throw new AppException($e->getError()->message);
        }catch (\Stripe\Exception\CardException $e) {
            throw new AppException($e->getError()->message);
        }

    }


    public  function refund ($cardId, $customerId,$amount) {
        try {
            $charge = $this->stripe->charges->create([
                'amount' => round(($amount)*100),
                'currency' => 'USD',
                'customer' => $customerId,
                'source' => $cardId,
              ]);
              return $charge;
        }catch(\Stripe\Exception\InvalidRequestException  $e) {
            throw new AppException($e->getError()->message);
        }catch (\Stripe\Exception\CardException $e) {
            throw new AppException($e->getError()->message);
        }

    }



    
    
}


