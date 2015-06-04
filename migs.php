<?php

/* MIGS Payment Gateway Class */

class MIGS extends WC_Payment_Gateway {

    // Setup our Gateway's id, description and other values
    function __construct() {

        // The global ID for this Payment method
        $this->id = "migs";

        // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
        $this->method_title = __("MIGS", 'migs');

        // The description for this Payment Gateway, shown on the actual Payment options page on the backend
        $this->method_description = __("MIGS Payment Gateway Plug-in for WooCommerce", 'migs');

        // The title to be used for the vertical tabs that can be ordered top to bottom
        $this->title = __("MIGS", 'migs');

        // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
        $this->icon = MIGS_PLUGIN_URL . 'images/migs_icon.jpg';

        // Bool. Can be set to true if you want payment fields to show on the checkout 
        // if doing a direct integration, which we are doing in this case
        $this->has_fields = false;

        // Supports the default credit card form
        //$this->supports = array('default_credit_card_form');
        // This basically defines your settings which are then loaded with init_settings()
        $this->init_form_fields();

        // After init_settings() is called, you can get the settings and load them into variables, e.g:
        // $this->title = $this->get_option( 'title' );
        $this->init_settings();

        // Turn these settings into variables we can use
        foreach ($this->settings as $setting_key => $value) {
            $this->$setting_key = $value;
        }
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'migs_response_handler'));
        // Lets check for SSL
        //add_action('admin_notices', array($this, 'do_ssl_check'));
        // Save settings
        if (is_admin()) {
            // Versions over 2.0
            // Save our administration options. Since we are not going to be doing anything special
            // we have not defined 'process_admin_options' in this class so the method in the parent
            // class will be used instead
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
        add_action('woocommerce_receipt_migs', array($this, 'migs_receipt_page'));
        //add_action('woocommerce_thankyou_migs', array($this, 'migs_response_handler'));
    }

// End __construct()
    // Build the administration fields for this specific Gateway
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable / Disable', 'migs'),
                'label' => __('Enable this payment gateway', 'migs'),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'migs'),
                'type' => 'text',
                'desc_tip' => __('Payment title the customer will see during the checkout process.', 'migs'),
                'default' => __('Master card', 'migs'),
            ),
            'description' => array(
                'title' => __('Description', 'migs'),
                'type' => 'textarea',
                'desc_tip' => __('Payment description the customer will see during the checkout process.', 'migs'),
                'default' => __('Pay securely using your master card.', 'migs'),
                'css' => 'max-width:350px;'
            ),
            'access_code' => array(
                'title' => __('MIGS Access Code', 'migs'),
                'type' => 'text',
                'desc_tip' => __('This is the Access Code MIGS when you signed up for an account.', 'migs'),
            ),
            'merchant_id' => array(
                'title' => __('MIGS Merchant ID', 'migs'),
                'type' => 'text',
                'desc_tip' => __('This is the Merchant ID when you signed up for an account.', 'migs'),
            ),
            'merchant_secret_key' => array(
                'title' => __('MIGS Secret Key', 'migs'),
                'type' => 'password',
                'desc_tip' => __('This is Mertchant Secret Key when you signed up for an account.', 'migs'),
            ),
            'environment' => array(
                'title' => __('MIGS Test Mode', 'migs'),
                'label' => __('Enable Test Mode', 'migs'),
                'type' => 'checkbox',
                'description' => __('Place the payment gateway in test mode.', 'migs'),
                'default' => 'no',
            )
        );
    }

    //Submit payment and handle response
    public function process_payment($order_id) {
        global $woocommerce;

        //Get this Order's information so that we know
        //who to charge and how much
        $customer_order = new WC_Order($order_id);

        /* $payment_fields = array(
          'vpc_AccessCode' => trim($this->access_code),
          'vpc_Amount' => '100',//$this->get_exact_amount($customer_order->order_total),
          'vpc_Command' => 'pay',
          'vpc_Locale' => 'en',
          'vpc_MerchTxnRef' => $order_id,
          'vpc_Merchant' => trim($this->merchant_id),
          'vpc_OrderInfo' => 'This is for test',
          'vpc_ReturnURL' => $this->get_return_url($customer_order),
          'vpc_Version' => 1
          );
          $hashData = $this->merchant_secret_key;

          foreach ($payment_fields as $key => $value) {
          $hashData .= $value;
          }

          $config_params = http_build_query($payment_fields);
          $config_params .= '&vpc_SecureHash=' . strtoupper(md5($hashData));
          $url = 'https://migs.mastercard.com.au/vpcpay?' . $config_params; */

        // Redirect to thank you page
        return array(
            'result' => 'success',
            'redirect' => $customer_order->get_checkout_payment_url(true)
        );

        //,
    }

    // Validate fields
    public function validate_fields() {
        return true;
    }

    public function do_ssl_check() {
        if ($this->enabled == "yes") {
            if (get_option('woocommerce_force_ssl_checkout') == "no") {
                echo "<div class=\"error\"><p>" . sprintf(__("<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>"), $this->method_title, admin_url('admin.php?page=wc-settings&tab=checkout')) . "</p></div>";
            }
        }
    }

    public function get_exact_amount($amount) {
        return($amount * 100);
    }

    public function migs_receipt_page($order_id) {
        global $woocommerce;
        $customer_order = new WC_Order($order_id);
        $redirect_url = add_query_arg('wc-api', get_class($this), site_url());

        $currency = 'QAR';
        //'vpc_Currency' => $currency,
        $payment_fields = array(
            'vpc_AccessCode' => trim($this->access_code),
            'vpc_Amount' => $this->get_exact_amount($customer_order->order_total),
            'vpc_Command' => 'pay',
            'vpc_Locale' => 'en',
            'vpc_MerchTxnRef' => $order_id,
            'vpc_Merchant' => trim($this->merchant_id),
            'vpc_OrderInfo' => 'This is for test',
            'vpc_ReturnURL' => $redirect_url, //$this->get_return_url($customer_order),
            'vpc_Version' => 1
        );
        $hashData = $this->merchant_secret_key;

        foreach ($payment_fields as $key => $value) {
            $hashData .= $value;
        }

        $config_params = http_build_query($payment_fields);
        $config_params .= '&vpc_SecureHash=' . strtoupper(md5($hashData));
        $url = 'https://migs.mastercard.com.au/vpcpay?' . $config_params;

        $payment_form = '<form id="migs_frm" action="' . $url . '" method="get">';

        $payment_form .= '<label><input type="checkbox" name="migs_terms_cond" required="true" /></label><a href="">Terms & conditions</a>';

        $payment_form .= '<input type="submit" name="migs_btn_submit" value="Pay" />';

        $payment_form .= '</form>';

        /*$script = '<script type="text/javascript"> 
            jQuery(document).ready(function(){ jQuery("#migs_frm").on("submit", function(e){e.preventDefault(); var URL = jQuery("#migs_frm").prop("action"); window.location.href = URL; })}); 
            </script>';*/

        echo $payment_form ;
    }

    public function migs_response_handler() {
        global $woocommerce;
        $response = $_REQUEST;

        $order_id = $response['vpc_MerchTxnRef'];
        $customer_order = new WC_Order($order_id);

        $amount = $this->null2unknown($_GET["vpc_Amount"]);
        $locale = $this->null2unknown($_GET["vpc_Locale"]);
        $batchNo = $this->null2unknown($_GET["vpc_BatchNo"]);
        $command = $this->null2unknown($_GET["vpc_Command"]);
        $message = $this->null2unknown($_GET["vpc_Message"]);
        $version = $this->null2unknown($_GET["vpc_Version"]);
        $cardType = $this->null2unknown($_GET["vpc_Card"]);
        $orderInfo = $this->null2unknown($_GET["vpc_OrderInfo"]);
        $receiptNo = $this->null2unknown($_GET["vpc_ReceiptNo"]);
        $merchantID = $this->null2unknown($_GET["vpc_Merchant"]);
        $authorizeID = $this->null2unknown($_GET["vpc_AuthorizeId"]);
        $merchTxnRef = $this->null2unknown($_GET["vpc_MerchTxnRef"]);
        $transactionNo = $this->null2unknown($_GET["vpc_TransactionNo"]);
        $acqResponseCode = $this->null2unknown($_GET["vpc_AcqResponseCode"]);
        $txnResponseCode = $this->null2unknown($_GET["vpc_TxnResponseCode"]);
        

// 3-D Secure Data
        $verType = array_key_exists("vpc_VerType", $_GET) ? $_GET["vpc_VerType"] : "No Value Returned";
        $verStatus = array_key_exists("vpc_VerStatus", $_GET) ? $_GET["vpc_VerStatus"] : "No Value Returned";
        $token = array_key_exists("vpc_VerToken", $_GET) ? $_GET["vpc_VerToken"] : "No Value Returned";
        $verSecurLevel = array_key_exists("vpc_VerSecurityLevel", $_GET) ? $_GET["vpc_VerSecurityLevel"] : "No Value Returned";
        $enrolled = array_key_exists("vpc_3DSenrolled", $_GET) ? $_GET["vpc_3DSenrolled"] : "No Value Returned";
        $xid = array_key_exists("vpc_3DSXID", $_GET) ? $_GET["vpc_3DSXID"] : "No Value Returned";
        $acqECI = array_key_exists("vpc_3DSECI", $_GET) ? $_GET["vpc_3DSECI"] : "No Value Returned";
        $authStatus = array_key_exists("vpc_3DSstatus", $_GET) ? $_GET["vpc_3DSstatus"] : "No Value Returned";


        if ($txnResponseCode == 0) {
            
            $customer_order->add_order_note(__('MIGS payment completed.', 'migs'));

            // Mark order as Paid
            $customer_order->payment_complete();

            // Empty the cart (Very important step)
            $woocommerce->cart->empty_cart();
            update_post_meta($order_id, 'unique_3d_transaction_identifier', $xid);
            update_post_meta($order_id, '3d_authentication_value', $token);
            update_post_meta($order_id, '3d_electronics_commerce', $acqECI);
            update_post_meta($order_id, '3d_authentication_schema', $verType);
            update_post_meta($order_id, '3d_security_level', $verSecurLevel);
            update_post_meta($order_id, '3d_enrolled', $enrolled);
            update_post_meta($order_id, '3d_auth_status', $authStatus);
            // Redirect to thank you page
            wp_redirect($this->get_return_url($customer_order));
            exit;
            
            
        } else {
            wc_add_notice('Message: ' . $this->getResponseDescription($txnResponseCode) . '', 'error');
            // Add note to the order for your reference
            $customer_order->add_order_note('Error: ' . $this->getResponseDescription($txnResponseCode));
            wp_redirect($customer_order->get_checkout_payment_url(true));
            exit;
        }

        
    }

    public function null2unknown($data) {
        if ($data == "") {
            return "No Value Returned";
        } else {
            return $data;
        }
    }

    public function getStatusDescription($statusResponse) {
        if ($statusResponse == "" || $statusResponse == "No Value Returned") {
            $result = "3DS not supported or there was no 3DS data provided";
        } else {
            switch ($statusResponse) {
                Case "Y" : $result = "The cardholder was successfully authenticated.";
                    break;
                Case "E" : $result = "The cardholder is not enrolled.";
                    break;
                Case "N" : $result = "The cardholder was not verified.";
                    break;
                Case "U" : $result = "The cardholder's Issuer was unable to authenticate due to some system error at the Issuer.";
                    break;
                Case "F" : $result = "There was an error in the format of the request from the merchant.";
                    break;
                Case "A" : $result = "Authentication of your Merchant ID and Password to the ACS Directory Failed.";
                    break;
                Case "D" : $result = "Error communicating with the Directory Server.";
                    break;
                Case "C" : $result = "The card type is not supported for authentication.";
                    break;
                Case "S" : $result = "The signature on the response received from the Issuer could not be validated.";
                    break;
                Case "P" : $result = "Error parsing input from Issuer.";
                    break;
                Case "I" : $result = "Internal Payment Server system error.";
                    break;
                default : $result = "Unable to be determined";
                    break;
            }
        }
        return $result;
    }

    public function getResponseDescription($responseCode) {

        switch ($responseCode) {
            case "0" : $result = "Transaction Successful";
                break;
            case "?" : $result = "Transaction status is unknown";
                break;
            case "1" : $result = "Unknown Error";
                break;
            case "2" : $result = "Bank Declined Transaction";
                break;
            case "3" : $result = "No Reply from Bank";
                break;
            case "4" : $result = "Expired Card";
                break;
            case "5" : $result = "Insufficient funds";
                break;
            case "6" : $result = "Error Communicating with Bank";
                break;
            case "7" : $result = "Payment Server System Error";
                break;
            case "8" : $result = "Transaction Type Not Supported";
                break;
            case "9" : $result = "Bank declined transaction (Do not contact Bank)";
                break;
            case "A" : $result = "Transaction Aborted";
                break;
            case "C" : $result = "Transaction Cancelled";
                break;
            case "D" : $result = "Deferred transaction has been received and is awaiting processing";
                break;
            case "F" : $result = "3D Secure Authentication failed";
                break;
            case "I" : $result = "Card Security Code verification failed";
                break;
            case "L" : $result = "Shopping Transaction Locked (Please try the transaction again later)";
                break;
            case "N" : $result = "Cardholder is not enrolled in Authentication scheme";
                break;
            case "P" : $result = "Transaction has been received by the Payment Adaptor and is being processed";
                break;
            case "R" : $result = "Transaction was not processed - Reached limit of retry attempts allowed";
                break;
            case "S" : $result = "Duplicate SessionID (OrderInfo)";
                break;
            case "T" : $result = "Address Verification Failed";
                break;
            case "U" : $result = "Card Security Code Failed";
                break;
            case "V" : $result = "Address Verification and Card Security Code Failed";
                break;
            default : $result = "Unable to be determined";
        }
        return $result;
    }

}

// End of SPYR_AuthorizeNet_AIM
//6768?key=wc_order_55003e06463a4&vpc_3DSECI=01&vpc_3DSXID=N8IoWqjHuqErMRVFb4H%2FOsqNnLo%3D&vpc_3DSenrolled=Y&vpc_3DSstatus=A&vpc_AVSRequestCode=Z&vpc_AVSResultCode=Unsupported&vpc_AcqAVSRespCode=Unsupported&vpc_AcqCSCRespCode=Unsupported&vpc_AcqResponseCode=00&vpc_Amount=200&vpc_AuthorizeId=640799&vpc_BatchNo=20150312&vpc_CSCResultCode=Unsupported&vpc_Card=MC&vpc_Command=pay&vpc_Locale=en&vpc_MerchTxnRef=6768&vpc_Merchant=TESTDB91249&vpc_Message=Approved&vpc_OrderInfo=This+is+for+test&vpc_ReceiptNo=507101640799&vpc_SecureHash=91CE93046800BBC99997E5DABC8957C1&vpc_TransactionNo=1144&vpc_TxnResponseCode=0&vpc_VerSecurityLevel=06&vpc_VerStatus=M&vpc_VerToken=htLerxW6QIujYwAAAG6TAyUAAAA%3D&vpc_VerType=3DS&vpc_Version=1