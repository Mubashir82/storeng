<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class TestingController extends Controller
{
    public function index(){
        require_once('../vendor/autoload.php');

        $client = new \GuzzleHttp\Client();
        
        $response = $client->request('POST', 'https://securelink-staging.valorpaytech.com:4430/?sale=', [
          'body' => '{"appid":"WaJeJErcv5xpqZa2UZz6LZod5MSyyfJw","appkey":"53PWdki5U0PGSVhRnVoFsfVSbuDutsA8","epi":"2203082193","txn_type":"sale","amount":5,"cardnumber":"4111111111111111","expirydate":1236,"cvv":999,"cardholdername":"Michael Jordan","invoicenumber":"inv0001","orderdescription":"king size bed 10x12","surchargeAmount":10.2,"surchargeIndicator":1,"address1":"2 Jericho Plz","city":"Jericho","state":"NY","shipping_country":"US","billing_country":"US","zip":"50001","customer_email":"0","customer_sms":"1","merchant_email":"0"}',
          'headers' => [
            'accept' => 'application/json',
            'content-type' => 'application/json',
          ],
        ]);
        
        echo $response->getBody();
        // // Create a Guzzle client instance
        // $client = new Client(['timeout' => 10]);
        // // Set the request parameters
        // $url = 'https://securelink-staging.valorpaytech.com:4430/?sale=';
        // $body = '{"appid":"WaJeJErcv5xpqZa2UZz6LZod5MSyyfJw","appkey":"53PWdki5U0PGSVhRnVoFsfVSbuDutsA8","epi":"2203082193","txn_type":"sale","amount":5,"cardnumber":"4111111111111111","expirydate":1236,"cvv":999,"cardholdername":"Michael Jordan","invoicenumber":"inv0001","orderdescription":"king size bed 10x12","surchargeAmount":10.2,"surchargeIndicator":1,"address1":"2 Jericho Plz","city":"Jericho","state":"NY","shipping_country":"US","billing_country":"US","zip":"50001","customer_email":"0","customer_sms":"1","merchant_email":"0"}';
        // $headers = [
        //     'accept' => 'application/json',
        //     'content-type' => 'application/json',
        // ];
        // // Make the POST request
        // $response = $client->request('POST', $url, [
        //     'body' => $body,
        //     'headers' => $headers,
        // ]);
        // // Get and return the response body
        // return $response->getBody();
    }
}
