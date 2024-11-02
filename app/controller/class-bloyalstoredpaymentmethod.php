<?php





// If this file is called directly, abort.

defined( 'ABSPATH' ) or die( 'No script!' );



add_action( 'plugins_loaded', 'bloyal_stored_payment_gateway' );



require_once BLOYAL_DIR . '/app/controller/bloyal_payment_controller.php';

require_once BLOYAL_DIR . '/app/controller/bloyal_logger_service.php';

require_once BLOYAL_DIR . '/app/controller/class-bloyalcontroller.php';



if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {



    function bloyal_stored_payment_gateway(){



        class Bloyal_Stored_Payment_Gateway extends WC_Payment_Gateway {

            /**

             * Constructor for the gateway.

             */

            function __construct() {

                $this->bloyalControllerObj      = new BloyalController();

                $this->domain                   = 'bloyal_stored_payment_method';

                $this->id                       = 'bloyal_stored_payment_method';

                $this->icon                     = apply_filters( 'woocommerce_offline_icon', '' );

                $this->has_fields               = true;

                $this->method_title             = __( 'bLoyal Stored Payment', 'bloyal_stored_payment_method' );

                $this->method_description       = __( 'This is Stored payment Gateway by bLoyal', 'bloyal_stored_payment_method' );

                $this->domain_name              = get_option( 'bloyal_domain_name' );

                $this->device_code              = get_option( 'Device_Code' );

                $this->loyalty_engine_url       = get_option( 'loyalty_engine_api_url' );

                $this->payment_snippets_profile = $this->bloyal_stored_payment_method_websnippetprofiles();//

                $this->init_form_fields();

                $this->init_settings();

                $this->supports                 = array(

                    'products',

                    'refunds',

                );

                $this->title                    = $this->get_option( 'title' );

                $this->description              = $this->get_option('description');

                $this->enabled                  = $this->get_option('enabled');

                $this->instructions             = $this->get_option( 'instructions', $this->description );

                $this->stored_payment_web_snippets_profile = $this->get_option('stored_payment_web_snippets_profile');



                add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

                add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );

                add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

    

            }



            //payment method is enable or disable from here...

            public function init_form_fields() {

               

                $this->form_fields = array(

                    'enabled'     => array(

                        'title'   => __( 'Enable/Disable', 'bloyal_stored_payment_method' ),

                        'type'    => 'checkbox',

                        'label'   => __( 'Enable bLoyal Stored Payment', 'bloyal_stored_payment_method' ),

                        'default' => 'No',

                    ),

                    'title'        => array(

                        'title'       => __( 'Title', 'bloyal_stored_payment_method' ),

                        'type'        => 'text',

                        'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'bloyal_stored_payment_method' ),

                        'default'     => __( 'Stored Payment', 'bloyal_stored_payment_method' ),

                        'desc_tip'    => true,

                    ),

                    

                    'description' => array(

                        'title'         => __( 'Description', 'bloyal_stored_payment_method' ),

                        'type'          => 'text',

                        'description'   => __( 'This controls the title for the payment method the customer sees during checkout.', 'bloyal_stored_payment_method' ),

                        'default'       => __( 'Amount will be deducted from your stored payment.', 'bloyal_stored_payment_method' ),

                        'desc_tip'      => true,

                    ),

                    'validate'     => array(

                            'title'       => __( 'Validate', 'bloyal_stored_payment_method' ),

                            'type'        => 'button',

                            'description' => __( 'Payment method description that the customer will see on your checkout.', 'bloyal_stored_payment_method' ),

                            'default'     => __( '', 'bloyal_stored_payment_method' ),

                            'desc_tip'    => true,

                        ),

                    'instructions' => array(

                        'title'       => __( 'Instructions', 'bloyal_stored_payment_method' ),

                        'type'        => 'textarea',

                        'description' => __( 'Instructions that will be added to the thank you page and emails.', 'bloyal_stored_payment_method' ),

                        'default'     => __( 'Instructions that will be added to the thank you page and emails.', 'bloyal_stored_payment_method' ),

                        'desc_tip'    => true,

                    ),

                    'stored_payment_web_snippets_profile' => array(

                        'title'         => __( 'bLoyal Stored Payment Snippets', 'bloyal_stored_payment_method' ),

                        'class'         => array( 'stored_payment_dropdown' ),

                        'type'          => 'select',

                        'options'       =>   $this->payment_snippets_profile,

                        'desc_tip'    => true,

                    ),

                );

            }





            /**

             * Output for the order received page.

             */

            public function thankyou_page() {

                if ( $this->instructions ) {

                    echo wpautop( wptexturize( esc_attr( $this->instructions ) ) );

                }

            }



            /**

             * Add content to the WC emails.

             *

             * @access public

             * @param WC_Order $order

             * @param bool     $sent_to_admin

             * @param bool     $plain_text

             */



            public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

                try {

                    if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {

                        echo wpautop( wptexturize( esc_attr( $this->instructions ) ) ) . PHP_EOL;

                    }

                } catch ( Exception $e ) {

                    bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );

                }

            }

            

            public function payment_fields() {

                $chosen_payment_method = WC()->session->get('chosen_payment_method');

                ?>

                <fieldset>

                    <!-- Stored payment loader after select stored payment method -->

                   <label id="storedPaymentMessage" value="">checking...</label>

                   <div id="loading_resubmit" class="loading" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url( BLOYAL_URL.'assets/images/puff.svg' ); ?>">Please Wait</p> </div>

                   <div class="stored-payment-render"></div>

                </fieldset>

                <script type="text/javascript">

                    jQuery(document).ready(function($) {

                        var chosen_payment_option = '<?php echo esc_js($chosen_payment_method); ?>';

                        if(chosen_payment_option === 'bloyal_stored_payment_method') {

                            $("input[type='radio'][value='bloyal_stored_payment_method']:checked").prop('checked', false);

                        }

                        sessionStorage.removeItem('stored_payment');

                        if(sessionStorage.getItem('stored_payment') === null) {

                           

                            $("input[type='radio'][value='bloyal_stored_payment_method']").click(function(){

                                $('.stored-payment-render').html('');

                                stored_payment_snippets(this.value);

                            });

                        }

                        // stored payment snippets code

                        function stored_payment_snippets(selected_payment_value) {

                            //$('.stored-payment-render').html(" ");

                            var data = {

                                action: 'get_stored_payment_method'

                            };

                            $.post(ajaxurl, data, function(responseData) {

                                if (responseData != null && responseData != '') {

                                    var payment = jQuery.parseJSON( responseData );

                                    if (payment != null) {

                                        sessionStorage.setItem('stored_payment', payment.html);

                                        var is_logged_in_user = payment.is_logged_in;

                                        var bl_session_key    = payment.session_key;



                                        $(document).ajaxStart(function() {

                                            $("#storedPaymentMessage").css("display", "block");

                                        });

                                        $(document).ajaxComplete(function() {

                                            $("#storedPaymentMessage").css("display", "none");

                                        });

                                        if(is_logged_in_user === true) {

                                            sessionStorage.setItem('bL_sk', bl_session_key);

                                        }else {

                                            sessionStorage.removeItem('bL_sk');

                                        }

                                        $('.stored-payment-render').html(payment.html);

                                    }    

                                }

                                //jQuery(document.body).trigger("update_checkout");

                                return false;

                            });

                        }

                    });

                </script>

                <?php

            }

            

           function process_payment( $order_id ) {

                try {

                    $order           = wc_get_order( $order_id );

                    $order           = new WC_Order( $order_id );

                    $paymentMethod   = $order->get_payment_method();

                    $orderTotal      =  round(floatval($order->get_total()), 2);

                    $autherizeResult = $this->stored_payment_authorize( $order_id, $orderTotal );



                    if ( $autherizeResult['result'] === true ) {

                        $order->add_order_note( 'Payment done using stored payment. Transaction ID : ' . $autherizeResult['transaction_id'] );

                        $order->payment_complete();



                        return array(

                            'result'   => 'success',

                            'redirect' => $this->get_return_url( $order ),

                        );

                    } else {

                        wc_add_notice( $autherizeResult['result'], 'error' );

                        return array(

                            'result'  => 'failure',

                            'message' => $autherizeResult['message'],

                        );

                    }

                    

                } catch ( Exception $exception ) {

                    bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );

                    echo 'Got Exception : ' . esc_attr($exception);

                    return array(

                        'result'  => 'failure',

                        'message' => 'Unable to process stored payment.',

                    );

                }

            }



            private function stored_payment_authorize( $order_id, $amount ) {

                

                $authorize_payment_request = $this->make_stored_payment_authorize_request( $order_id, $amount );      

                bLoyalLoggerService::write_custom_log( "Stored Payment Authorize Request\r\n" .json_encode( $authorize_payment_request ) . "\r\n ======================\r\n", 1 );



                $result = $this->bloyalControllerObj->send_curl_request( $authorize_payment_request, 'payments/paymentmethods/commands/authorize', 'loyaltyengine', 1 );



                bLoyalLoggerService::write_custom_log( "\n\r==========Stored payment authorized response============ \r\n" . json_encode( $result ) );



                $use_order_engine      = get_option( 'bloyal_use_order_engine' );

                if ($use_order_engine !== 'true' && isset($result->status) && $result->status === "success" ) {

                    $payment_uid =  $result->data->PaymentUid;

                    $captureResult = $this->stored_payment_capture( $order_id, $amount, $payment_uid );

                    if ( $captureResult['result'] == true ) {

                        add_post_meta( $order_id, '_stored_payment_amount', $amount, true );

					    add_post_meta( $order_id, '_stored_payment_transaction_id', $captureResult['transaction_id'], true );

                        return array(

                            'result'   => true,

                            'transaction_id' => $captureResult['transaction_id'],

                        );

                    } else {

                        wc_add_notice( $captureResult['result'], 'error' );

                        return array(

                            'result'  => false,

                            'message' => $captureResult['message'],

                        );

                    }

                } else {

                   if ( isset($result->status) && $result->status === "success" ) {

                        add_post_meta( $order_id, '_stored_payment_amount', $amount, true );

					    add_post_meta( $order_id, '_stored_payment_transaction_id', $result->data->TransactionCode, true );

                        return array(

                            'result'   => true,

                            'transaction_id' => $result->data->TransactionCode,

                        );

                    } else {

                        wc_add_notice( $result->status, 'error' );

                        return array(

                            'result'  => false,

                            'message' => $result->message,

                        );

                    }

                }



                if( isset($result->status) && $result->status === 'error' ) {

                    $msg = $result->message;

                    wc_add_notice( $msg, 'error' );

                    return array(

                        'result'  => false,

                        'message' => $msg,

                    );

                }



                return array(

                    'result'  => false,

                    'message' => "Payment method response not found !",

                );     

            }

    

            private function make_stored_payment_authorize_request( $order_id, $amount ) {

                try {

                    $cart_data       = WC()->session->get( 'bloyal_cart_data');

                    $payment_uid     = WC()->session->get( 'stored_payment_uid');

                    $order           = new WC_Order( $order_id );

                    $user_id         = $order->get_user_id();

                    $customer        = new WC_Customer( $user_id );

                    $billing_email   = $customer->get_billing_email();

                    $customer_uid    = $this->bloyalControllerObj->bloyal_fetch_customer_uid( $user_id, $billing_email );

                    $request_authorize = array(

                        "MethodUid"            => $payment_uid, //'f4239db7-2318-49af-96e7-53d5349af6cf',

                        "CartUid"              => $cart_data->Cart->Uid,

                        "CartExternalId"       => "",

                        "CartSourceExternalId" => "",

                        "CustomerUid"          => $cart_data->Cart->Customer->Uid,

                        "CustomerExternalId"   => $cart_data->Cart->Customer->Id,

                        "CustomerCode"         => $cart_data->Cart->Customer->Code,

                        "SecurityCode"         => "",

                        "Amount"               => floatval($amount),

                        "ReferenceNumber"      => "",

                        "Comment"              =>  "",

                        "CashierUid"           => "00000000-0000-0000-0000-000000000000",

                        "CashierExternalId"    => "",

                        "CashierCode"          => ""

                    );

                    return $request_authorize;

                } catch ( Exception $e ) {

                    bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );

                }

            }



            //bLoyal capture payment 

            public function stored_payment_capture( $order_id, $amount, $payment_uid ) {  

                $captured_payment_request = $this->make_stored_payment_capture_request( $order_id, $amount, $payment_uid );

                bLoyalLoggerService::write_custom_log( "Stored Payment captured Request\r\n" .json_encode( $captured_payment_request ) . "\r\n ======================\r\n", 1 );

                $result = $this->bloyalControllerObj->send_curl_request( $captured_payment_request, 'payments/paymentmethods/commands/capture', 'loyaltyengine', 1 );

                bLoyalLoggerService::write_custom_log( "\n\r==========Stored payment captured response============ \r\n" . json_encode( $result ) );

                if ( isset($result->status) && $result->status === "success" ) {

                    add_post_meta( $order_id, '_stored_payment_amount', $amount, true );

                    add_post_meta( $order_id, '_stored_payment_transaction_id', $result->data->TransactionCode, true );

                    return array(

                        'result'         => true,

                        'transaction_id' => $result->data->TransactionCode,

                        'auth_code'      => '',

                    );

                } 



                if ( isset($result->status) && $result->status === 'error' ) {

                    $msg = $result->message;

                    wc_add_notice( $msg, 'error' );

                    return array(

                        'result'  => false,

                        'message' => $msg,

                    );

                }



                return array(

                    'result'         => false,

                    'transaction_id' => null,

                );   

            }



            public function make_stored_payment_capture_request( $order_id, $amount, $payment_uid ) {

                try {

                    $cart_data       = WC()->session->get( 'bloyal_cart_data');

                    $request_captured = array(

                        

                        "CartUid"              => $cart_data->Cart->Uid,

                        "CartExternalId"       => "",

                        "CartSourceExternalId" => "",

                        "PaymentUid"           => $payment_uid,

                        "Amount"               => floatval($amount),

                        "ReferenceNumber"      => "",

                        "Comment"              =>  ""

                        

                    );

                   

                    return $request_captured;

                } catch ( Exception $e ) {

                    bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );

                }

            }



            public function process_refund( $order_id, $amount = null, $reason = '' ) {

                try {

                    $order                 = new WC_Order( $order_id );

                    $bloyal_obj            = new BloyalController();

					$refund_transaction_id = $bloyal_obj->bloyal_refund_stored_payment( $order_id, $amount );

					if ( $refund_transaction_id ) {

						return true;

					}

                    return false;

                } catch ( Exception $e ) {

                    bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );

                }

            }



        

            /**

             * Function to fetch Stored Payment Method options call web snippets summary API by bLoyal

             *

             * @return api result

             */

            public function bloyal_stored_payment_method_websnippetprofiles() {

                try {

                    // this code will run when the user is logged in
                    if(is_user_logged_in() && $this->get_option('enabled') == 'yes'){
                        $action   = $this->domain_name."/".$this->device_code."/snippets/websnippetprofiles/summaries?type=PaymentMethods";
                        $post_url = $this->loyalty_engine_url . '/api/v4/' . ( $action );
                        $args     = array(

                            'headers' => array(

                                'Content-Type' => 'application/json',

                            ),

                            'body'    => $content,

                            'method'  => 'GET',

                            'timeout' => 45,

                        );

                        $response        = wp_remote_get( $post_url, $args );

                        $response_status = wp_remote_retrieve_response_code( $response );

                        $response        = wp_remote_retrieve_body( $response );

                        $obj_response    = json_decode( $response, true );

                        if ( is_wp_error( $obj_response ) ) {

                            $error = $response->get_error_message();

                            return $error;

                        } else {

                            $payment_snippets_option = array();

                            if(!empty($obj_response['data']) && isset($obj_response['data']) && $obj_response['status'] === 'success') {

                                $payment_snippets_option['0'] = "Select payment snippets";

                                $payment_snippets_data        = $obj_response['data'];

                                $payment_snippets_array       = array();

                                foreach($payment_snippets_data as $payment_snippets_value ) {

                                    $payment_snippets_uid           = $payment_snippets_value['Uid'];

                                    $payment_snippets_code          = $payment_snippets_value['Code'];

                                    $payment_snippets_name          = $payment_snippets_value['Name'];

                                    $payment_snippets_array['Code'] = $payment_snippets_code;

                                    $payment_snippets_array['Name'] = $payment_snippets_name;

                                    $payment_snippets_option[$payment_snippets_uid] = $payment_snippets_code.','.$payment_snippets_name;

                                    

                                    $payment_snippets_json = json_encode( $payment_snippets_array );

                                    update_option($payment_snippets_uid, $payment_snippets_json);

                                }

                                return  $payment_snippets_option;

                            }else {

                                return  $payment_snippets_option;

                            }

                        }

                }

                } catch ( Exception $ex ) {

                    bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );

                }

            }





            public function payment_web_snippets_callback() {



                 try {

                    if(!empty($this->stored_payment_web_snippets_profile) && $this->stored_payment_web_snippets_profile  != '0') {

                        $cart_data                                  = WC()->session->get( 'bloyal_cart_data');

                        $page_id                                    = get_option( 'woocommerce_myaccount_page_id' );

                        $login_page                                 = get_permalink( $page_id );

                        $store_payment_json_data                    = get_option($this->stored_payment_web_snippets_profile);

                        $store_payment_json_values                  = json_decode($store_payment_json_data, TRUE);

                        $snippet_code                               = $store_payment_json_values['Code'];

                        $bloyal_snippet_args                        = array();

                        $bloyal_snippet_args['DeviceCode']          = $this->device_code;

                        $bloyal_snippet_args['CartUid']             = $cart_data->Cart->Uid;

                        $bloyal_snippet_args['LoginUrl']            = $login_page;

                        $bloyal_snippet_args['OnSnippetComplete']   = "PaymentSnippetCompleteFn";

                        $bloyal_snippet_args['PaymentRedirectToHome']   = true;

                        $snippets_api_url                           = get_option( 'web_snippets_api_url' );

                        $snippets_src                               = 'https://snippetsbeta.bloyal.io/bLoyalSnippetLoader.js';

                        $alert_snippet_div                          = "<div data-bloyal-snippet-code='".$snippet_code."' data-bloyal-login-domain='".$this->domain_name."'  data-bloyal-snippet-args='".wp_json_encode($bloyal_snippet_args, TRUE)."' id='root'></div><script> function PaymentSnippetCompleteFn(paymentMethod) { jQuery.ajax( { type: 'POST', dataType : 'JSON',url: httpAdminUrl, data: { action: 'bloyal_stored_payment_method', method_uid: paymentMethod.Uid }, beforeSend: function(){  jQuery('#loading_resubmit').show(); }, success:function(responseData){  }, complete:function(responseData){jQuery('#loading_resubmit').hide(); } } ); console.log(paymentMethod); } </script>";

                         

                        return $alert_snippet_div;

                    } else {

                        $alert_snippet_div = "Payment snippets not found, Place order with another payment method !";

                        return $alert_snippet_div;

                    }

                } catch ( Exception $ex ) {

                    bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );

                }

            }

        }

    }

}

