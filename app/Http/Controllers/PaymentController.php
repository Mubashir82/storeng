<?php

namespace App\Http\Controllers;
use App\BusinessLocation;
use Illuminate\Http\Request;
use Omnipay\Omnipay;
use App\Payment;
use DB;
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
        // print_r($request->input('location_id'));exit;
        $resp = [];
        $resp['success'] = 0;
        $resp['data'] = '';
        $resp['message'] = '';
        $location_id = $request->input('location_id');
        $valor_appid = null;
        $valor_appkey = null;
        $valor_epi = null;
        if(!$location_id){
            $business_id = request()->session()->get('user.business_id');
            $check_permission = true;
            $append_id = true;
            $query = BusinessLocation::where('business_id', $business_id)->Active();
            if ($check_permission) {
                $permitted_locations = auth()->user()->permitted_locations();
                if ($permitted_locations != 'all') {
                    $query->whereIn('id', $permitted_locations);
                }
            }
            if ($append_id) {
                $query->select(
                    DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"),
                    'id',
                    'receipt_printer_type',
                    'selling_price_group_id',
                    'default_payment_accounts',
                    'invoice_scheme_id'
                );
            }
            $result = $query->get();
            $locations = $result->pluck('id');
            $location_id = $locations[0];
        }
        $query = DB::table('business_locations')->where('id', '=', $location_id)->get();
        $valor_appid = $query[0]->valor_appid;
        $valor_appkey = $query[0]->valor_appkey;
        $valor_epi = $query[0]->valor_epi;
        if($valor_appid && $valor_appkey && $valor_epi){
            $cc_number = $request->input('cc_number');
            $cc_holder_name = $request->input('cc_holder_name');
            $expiry_month = $request->input('expiry_month');
            $expiry_year = $request->input('expiry_year');
            $amount = $request->input('amount');
            try {
                $url = 'https://securelink.valorpaytech.com/?sale=';
                $body = '{
                    "appid":"'.$valor_appid.'",
                    "appkey":"'.$valor_appkey.'",
                    "epi":"'.$valor_epi.'",
                    "txn_type":"sale",
                    "amount":'.$amount.',
                    "cardnumber":"'.$cc_number.'",
                    "expirydate":"'.$expiry_month.''.$expiry_year.'",
                    "cardholdername":"'.$cc_holder_name.'",
                    "surchargeAmount":0.0,
                    "surchargeIndicator":0,
                    "address1":"",
                    "city":"",
                    "state":"",
                    "shipping_country":"US",
                    "billing_country":"US",
                    "zip":"",
                    "customer_email":"0",
                    "customer_sms":"1",
                    "merchant_email":"0"
                }';
                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json',
                ];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                // $response = '{"error_no":"S00","success_url":true,"error_code":"00","amount":"0.05","tax":"","customfee":"0","msg":"APPROVED","desc":"APPROVAL 01577R ","additional_info":null,"clerk_id":null,"clerk_name":null,"clerk_label":null,"additionalKeyOne":null,"additionalKeyTwo":null,"additionalValueOne":null,"additionalValueTwo":null,"approval_code":"01577R","rrn":"334919334374","txnid":"1972099237","tran_no":3,"stan":1574,"is_partial_approve":0,"partial_amount":"000000000005","pan":"XXXX5245","card_type":null,"phone_number":"","email_id":"","zip":null,"card_holder_name":"LALWANI\/KASHIF","expiry_date":"09\/24","address":"","epi":"2225547719","channel":"ECOMM","token":"AD97490A5879ED4DB0C00E99FDA2C69C3239C727","card_brand":"Discover"}';
                if ($response === false) {
                    $resp['message'] = curl_error($ch);
                }
                else{
                    curl_close($ch);
                    DB::table('valorpay_payments')->insert(['response' => $response]);
                    $jsonArray = json_decode($response, true);
                    $error_no = $jsonArray['error_no'];
                    // $error_code = $jsonArray['error_code'];
                    // $success_url = $jsonArray['success_url'];
                    
                    if($error_no =='S00'){
                        $msg = $jsonArray['msg'];
                        $txnid = $jsonArray['txnid'];
                        $resp['success'] = 1;
                        $resp['data'] = ['transaction_id' => $txnid];
                        $resp['message'] = $msg.'. Your transaction id is: '. $txnid;
                        $resp['response'] = $jsonArray;
                    }
                    else{
                        $mesg = $jsonArray['mesg'];
                        $resp['message'] = $mesg;
                    }
                }
            } 
            catch( Exception $e ) {
                $resp['message'] = $e->getMessage();
            }
        }else{
            $resp['message'] = "Payment Method is not properly Integrated";
        }
        echo json_encode($resp);
        return;
    }
    public function charge_testing( Request $request ) {
        $resp = [];
        $resp['success'] = 0;
        $resp['data'] = '';
        $resp['message'] = '';
        $cc_number = $request->input('cc_number');
        $cc_holder_name = $request->input('cc_holder_name');
        $expiry_month = $request->input('expiry_month');
        $expiry_year = $request->input('expiry_year');
        $amount = $request->input('amount');
        try {
            $url = 'https://securelink-staging.valorpaytech.com/?sale=';
            $body = '{
                "appid":"WaJeJErcv5xpqZa2UZz6LZod5MSyyfJw",
                "appkey":"53PWdki5U0PGSVhRnVoFsfVSbuDutsA8",
                "epi":"2203082193",
                "txn_type":"sale",
                "amount":'.$amount.',
                "cardnumber":"'.$cc_number.'",
                "expirydate":"'.$expiry_month.''.$expiry_year.'",
                "cardholdername":"'.$cc_holder_name.'",
                "surchargeAmount":0.0,
                "surchargeIndicator":0,
                "address1":"",
                "city":"",
                "state":"",
                "shipping_country":"US",
                "billing_country":"US",
                "zip":"",
                "customer_email":"0",
                "customer_sms":"1",
                "merchant_email":"0"
            }';
            $headers = [
                'Accept: application/json',
                'Content-Type: application/json',
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if ($response === false) {
                $resp['message'] = curl_error($ch);
            }
            else{
                curl_close($ch);
                $jsonArray = json_decode($response, true);
                $error_no = $jsonArray['error_no'];
                $error_code = $jsonArray['error_code'];
                $success_url = $jsonArray['success_url'];
                $txnid = $jsonArray['txnid'];
                if($error_no =='S00' && $success_url == true && $error_code == '00'){
                    $msg = $jsonArray['msg'];
                    $resp['success'] = 1;
                    $resp['data'] = ['transaction_id' => $txnid];
                    $resp['message'] = $msg.'. Your transaction id is: '. $txnid;
                }
                else{
                    $mesg = $jsonArray['mesg'];
                    $resp['message'] = $mesg;
                }
            }
        } 
        catch( Exception $e ) {
            $resp['message'] = $e->getMessage();
        }
        echo json_encode($resp);
        return;
    }
    public function charge_old( Request $request ) {
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
