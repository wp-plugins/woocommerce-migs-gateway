<?php

/**
 * @author Pradipta Sarkar <pradipta.sarkar@infoway.us>
 */
/*
 * 
 * array('vpc_MerchTxnRef' => '',
  'vpc_Amount' => '',
  'vpc_OrderInfo' => '',
  'vpc_CardNum' => '',
  'vpc_CardExp' => '',
  'vpc_Currency' => '',
  'vpc_SecureHash' => '',)
 */

class Migs_class {

    protected $vpc_AccessCode;
    protected $vpc_Merchant;
    protected $vpc_Version = 1;
    protected $vpc_Command = 'pay';
    protected $payment_fields = array();
    protected $payment_gateway_url = 'https://migs.mastercard.com.au/vpcdps';

    public function __construct($accessCode, $merchant, array $payment_fields) {
        $this->vpc_AccessCode = $accessCode;
        $this->vpc_Merchant = $merchant;
        $this->setFields($payment_fields);
    }

    protected function setFields(array$payment_fields) {
        $this->payment_fields['vpc_Version'] = $this->vpc_Version;
        $this->payment_fields['vpc_Command'] = $this->vpc_Command;
        $this->payment_fields['vpc_AccessCode'] = $this->vpc_AccessCode;
        $this->payment_fields['vpc_Merchant'] = $this->vpc_Merchant;
        if (is_array($payment_fields) && count($payment_fields) > 0) {
            foreach ($payment_fields as $field => $value) {
                $this->payment_fields[$field] = $value;
            }
        }
    }

    protected function migsAPICall() {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->payment_gateway_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->payment_fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 100);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    public function doPayment() {
        
        $response = $this->migsAPICall();
      
        return $this->getResponse($response);
    }

    public function getResponse($response) {
        $total_resp = array();
        $parseResponse = explode('&', $response);

        if (is_array($parseResponse) && count($parseResponse) > 0) {
            foreach ($parseResponse as $resp) {
                $parse = explode('=', $resp);
                $total_resp[$parse[0]] = $parse[1];
            }
        }

        return $total_resp;
        
        /*$response_arr = array(
            'message' => $response['vpc_Message'],
            'amount' => $response['vpc_Amount'],
            'responseCode' => $response['vpc_TxnResponseCode'],
            'acqResponseCode' => $response['vpc_AcqResponseCode'],
            'transactionNo' => $response['vpc_TransactionNo'],
            'receiptNo' => $response['vpc_ReceiptNo'],
            'AuthorizeId' => $response['vpc_AuthorizeId']
        );*/

        
    }

}
