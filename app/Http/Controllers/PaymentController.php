<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Omnipay\Omnipay;
use App\Payment;

class PaymentController extends Controller {
    public $gateway;

    public function __construct() {
        $this->gateway = Omnipay::create( 'AuthorizeNetApi_Api' );
        $this->gateway->setAuthName( env( 'ANET_API_LOGIN_ID' ) );
        $this->gateway->setTransactionKey( env( 'ANET_TRANSACTION_KEY' ) );
        $this->gateway->setTestMode( true );
        //comment this line when move to 'live'
    }

    public function index() {
        return view( 'payment' );
    }

    public function charge( Request $request ) {
        $resp = [];
        $resp['success'] = 0;
        $resp['data'] = '';
        $resp['message'] = '';

        try {
            $creditCard = new \Omnipay\Common\CreditCard( [
                'number' => $request->input( 'cc_number' ),
                'expiryMonth' => $request->input( 'expiry_month' ),
                'expiryYear' => $request->input( 'expiry_year' ),
                'cvv' => $request->input( 'cvv' ),
            ] );

            // Generate a unique merchant site transaction ID.
            $transactionId = rand( 100000000, 999999999 );

            $response = $this->gateway->authorize( [
                'amount' => $request->input( 'amount' ),
                'currency' => 'USD',
                'transactionId' => $transactionId,
                'card' => $creditCard,
            ] )->send();

            if ( $response->isSuccessful() ) {

                // Captured from the authorization response.
                $transactionReference = $response->getTransactionReference();

                $response = $this->gateway->capture( [
                    'amount' => $request->input( 'amount' ),
                    'currency' => 'USD',
                    'transactionReference' => $transactionReference,
                ] )->send();

                $transaction_id = $response->getTransactionReference();
                $amount = $request->input( 'amount' );

                // Insert transaction data into the database
                $isPaymentExist = Payment::where( 'transaction_id', $transaction_id )->first();

                if ( !$isPaymentExist ) {
                    $payment = new Payment;
                    $payment->transaction_id = $transaction_id;
                    $payment->amount = $request->input( 'amount' );
                    $payment->currency = 'USD';
                    $payment->payment_status = 'Captured';
                    $payment->save();
                }

                $resp['success'] = 1;
                $resp['data'] = ['transaction_id' => $transaction_id];
                $resp['message'] = 'Payment is successful. Your transaction id is: '. $transaction_id;
            } else {
                // not successful
                $resp['message'] = $response->getMessage();
            }
        } catch( Exception $e ) {
            $resp['message'] = $e->getMessage();
        }
        echo json_encode($resp);
        return;
    }
}
