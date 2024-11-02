<?php

defined( 'ABSPATH' ) or die( 'No script!' );

add_action( 'plugins_loaded', 'bloyal_loyalty_dollar_payment_gateway' );

require_once BLOYAL_DIR . '/app/controller/bloyal_loyalty_dollar_payment_controller.php';

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function bloyal_loyalty_dollar_payment_gateway() {
		class Bloyal_Loyalty_Dollar_Gateway extends WC_Payment_Gateway {
			/**
			 * Constructor for the gateway.
			 */
			public function __construct() {
				$this->domain             = 'wc-gateway-bloyal-loyalty-dollar';
				$this->id                 = 'bloyal_loyalty_dollar';
				$this->icon               = apply_filters( 'woocommerce_offline_icon', '' );
				$this->has_fields         = true;
				$this->method_title       = __( 'bLoyal Loyalty Dollar', 'wc-gateway-bloyal-loyalty-dollar' );
				$this->method_description = __( 'Allows payments thorugh bloyal loyalty dollar.', 'wc-gateway-bloyal-loyalty-dollar' );
				$this->init_form_fields();
				$this->init_settings();
				$this->title        = $this->get_option( 'title' );
				$this->description  = $this->get_option( 'description' );
				$this->instructions = $this->get_option( 'instructions', $this->description );
				$this->supports     = array(
					'products',
					'refunds',
				);
				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
				add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
				add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
			}

			/**
			 * Initialize Gateway Settings Form Fields
			 */

			public function init_form_fields() {
				$this->form_fields = apply_filters(
					'wc_offline_form_fields',
					array(
						'enabled'      => array(
							'title'   => __( 'Enable/Disable', 'wc-gateway-bloyal-loyalty-dollar' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable bLoyal Loyalty Dollar', 'wc-gateway-bloyal-loyalty-dollar' ),
							'default' => 'No',
						),

						'title'        => array(
							'title'       => __( 'Title', 'wc-gateway-bloyal-loyalty-dollar' ),
							'type'        => 'text',
							'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-bloyal-loyalty-dollar' ),
							'default'     => __( 'bLoyal Loyalty Dollar', 'wc-gateway-bloyal-loyalty-dollar' ),
							'desc_tip'    => true,
						),

						'description'  => array(
							'title'       => __( 'Description', 'wc-gateway-bloyal-loyalty-dollar' ),
							'type'        => 'textarea',
							'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-bloyal-loyalty-dollar' ),
							'default'     => __( '', 'wc-gateway-bloyal-loyalty-dollar' ),
							'desc_tip'    => true,
						),

						'validate'     => array(
							'title'       => __( 'Validate', 'wc-gateway-bloyal-loyalty-dollar' ),
							'type'        => 'button',
							'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-bloyal-loyalty-dollar' ),
							'default'     => __( 'Amount will be deducted from your bloyal loyalty dollar balance.', 'wc-gateway-bloyal-loyalty-dollar' ),
							'desc_tip'    => true,
						),

						'instructions' => array(
							'title'       => __( 'Instructions', 'wc-gateway-bloyal-loyalty-dollar' ),
							'type'        => 'textarea',
							'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-bloyal-loyalty-dollar' ),
							'default'     => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-bloyal-loyalty-dollar' ),
							'desc_tip'    => true,
						),
					)
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

				if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
					echo wpautop( wptexturize( esc_attr( $this->instructions ) ) ) . PHP_EOL;
				}
			}

			public function payment_fields() {

				if ( $description = $this->get_description() ) {
					echo wpautop( wptexturize( esc_attr( $description ) ) );
				}
				$applyFullBalance = get_option( 'bloyal_apply_full_balance_loyalty' );
				
				
				$chosen_payment_method = WC()->session->get('chosen_payment_method');
                ?>
                <fieldset>
                    <!-- Stored payment loader after select stored payment method -->
                   <label id="storedPaymentMessage1" value="">checking...</label>
                   <div id="loading_resubmit" class="loading" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="<?php echo esc_url(BLOYAL_URL.'assets/images/puff.svg'); ?>">Please Wait</p> </div>
					<div id="custom_input">
						<?php if ( $applyFullBalance == 'false' ) { ?>
						<select id="select-loyalty-balance" style="display: none;"><option value="0">Choose Loyalty Balance Amount</option></select>
						<?php } ?>
						<p class="form-row form-row-wide">
						<input type="button" class="" name="check_loyalty_balance" id="check_loyalty_balance" value="Check Balance">
						<input type="button" class="" name="apply_loyalty_balance" id="apply_loyalty_balance" value="Apply Balance">
						<label id="loyaltyDollarMessage" value=""></label>
						</p>
						<p class="form-row form-row-wide">
						<label style="color:red; display: none;" id="insufficient_loyalty_bal" value=""></label>
						</p>
					</div>
                </fieldset>
				

		<script type="text/javascript">
		  jQuery(document).ready(function($) {
			  var chosen_payment_option = '<?php echo esc_js($chosen_payment_method); ?>';
                        if(chosen_payment_option === 'bloyal_loyalty_dollar') {
                            $("input[type='radio'][value='bloyal_loyalty_dollar']:checked").prop('checked', false);
                        }
                        sessionStorage.removeItem('loyalty_dollar');
                        if(sessionStorage.getItem('loyalty_dollar') === null) { 
                            $("input[type='radio'][value='bloyal_loyalty_dollar']").click(function(){
                            	//chetu code to hide checking message on payment method selection
                            	$('form.checkout').on('change', 'input[name="payment_method"]', function(){
                                    $("#storedPaymentMessage1").hide();
                                });
                                stored_payment_snippets(this.value);
                            });
                        }
						
						// stored payment snippets code
                        function stored_payment_snippets(selected_payment_value) {
                            var data = {
                                action: 'get_stored_payment_method'
                            };
                            $.post(ajaxurl, data, function(responseData) {
                                if (responseData != null && responseData != '') {
                                    var payment = jQuery.parseJSON( responseData );
                                    if (payment != null) {
                                        sessionStorage.setItem('loyalty_dollar', payment.html);
                                        var is_logged_in_user = payment.is_logged_in;
                                        var bl_session_key    = payment.session_key;

                                        $(document).ajaxStart(function() {
                                            $("#storedPaymentMessage1").css("display", "block");
                                        });
                                        $(document).ajaxComplete(function() {
                                            $("#storedPaymentMessage1").css("display", "none");
                                        });

                                        if(is_logged_in_user === true) {
                                            sessionStorage.setItem('bL_sk', bl_session_key);
											$('#check_loyalty_balance').click(function() {
														$("#storedPaymentMessage1").text('checking...');
													  $("#check_loyalty_balance").prop('disabled', true);
													  var data = {
														action: 'check_loyalty_dollar_balance'
													  }
													  $.post(ajaxurl, data, function(response) {
														var response_obj = $.parseJSON(response);
														var increment_of = response_obj.apply_incrementof;
														var cartSubtotal = response_obj.cart_total;
														var availableLoyaltyBalance = response_obj.balance;
														if(response_obj.status == 'error'){
														  $("#check_loyalty_balance").prop('disabled', false);
														  $("#loyaltyDollarMessage").text(response_obj.message);
														  return false;
														}
														
														if(availableLoyaltyBalance == 0){
														  $('#apply_loyalty_balance').prop('disabled', true);
														}
														if(response_obj.apply_fullbalance == "false") {
														  if(availableLoyaltyBalance > 0) {
															$("#select-loyalty-balance").show();
															var selectId = "#select-loyalty-balance";
															bLoyalloyaltyOptions(availableLoyaltyBalance, cartSubtotal, increment_of, selectId);
															$('#apply_loyalty_balance').prop('disabled', false);
														  } else {
															  $("#select-loyalty-balance").hide();
															  $("#apply_loyalty_balance").prop('disabled', true);
														  }
														}else {

														}
														$("#check_loyalty_balance").prop('disabled', false);
														$("#loyaltyDollarMessage").text(response_obj.message);
														return false;
													  });
													});
													$('#apply_loyalty_balance').click(function() {
													  $("#loyaltyDollarMessage").text('Just a moment ... ');
														<?php if ( $applyFullBalance == 'false' ) { ?>
													  var selectedBalance = $('#select-loyalty-balance').children("option:selected").val();
													  if(selectedBalance == '0') {
														$("#loyaltyDollarMessage").text("Please choose an amount!");
														 return false;
													  }
													  <?php } else { ?>
														var selectedBalance = 0;
													   <?php } ?>
													  var data = {
														action: 'apply_loyalty_dollar_balance',
														post_redeem_amount : selectedBalance
													  }
													  $.post(ajaxurl, data, function(response) {
														var response_obj = $.parseJSON(response);
														console.log(response_obj);
														if(response_obj.status == 'success'){
														  //If card balance is less then order total show message ......
														}else {
														}
														$("#loyaltyDollarMessage").text(response_obj.message);
														$("#check_balance").prop('disabled', false);
														$("#check_loyalty_balance").prop('disabled', true);
														$("#apply_loyalty_balance").prop('disabled', true);
														$('form.checkout').on('change', 'input[name="payment_method"]', function(){
															$(document.body).trigger('update_checkout');
														});
														// jQuery(document.body).trigger("update_checkout");
														return false;
													  });
													});
													  //render the loyalty amount options logic for dropdown....
													  function bLoyalloyaltyOptions(availableLoyaltyBalance, cartSubtotal, apply_in_increment_of, selectId) {
														 $(selectId).empty().append('<option value="0">Choose Loyalty Balance Amount</option>');
														if (Math.round(cartSubtotal) > Math.round(availableLoyaltyBalance)) {
														  var dropDownNumbers = availableLoyaltyBalance / apply_in_increment_of;
														  for (i = 1; i <= dropDownNumbers; i++) {
															var key    =  i * apply_in_increment_of;
															var value    =  i * apply_in_increment_of;
															$(selectId).append($("<option></option>").attr("value", key).text(value)); 
														  }  
														} else if (Math.round(availableLoyaltyBalance) > Math.round(cartSubtotal)) {
														  var applicableBalance = cartSubtotal;
														  var dropDownNumbers = applicableBalance / apply_in_increment_of;
														  for (i = 1; i <= dropDownNumbers; i++) {
															  var key   =  i * apply_in_increment_of;
															  var value   =  i * apply_in_increment_of;
															  $(selectId).append($("<option></option>").attr("value", key).text(value));
														  }
														} else if (Math.round(cartSubtotal) == Math.round(availableLoyaltyBalance)) {
														  var dropDownNumbers = cartSubtotal / apply_in_increment_of;
														  for (i = 1; i <= dropDownNumbers; i++) {
															  var key   =  i * apply_in_increment_of;
															  var value   =  i * apply_in_increment_of;
															  $(selectId).append($("<option></option>").attr("value", key).text(value));                  
														  }
														}
													  }
                                        }else {
                                            sessionStorage.removeItem('bL_sk');
                                            const currentPageUrl = (new URL(window.location.href));
                                            const websiteDomain = currentPageUrl.origin;
                                            window.location = websiteDomain+"/my-account?ReturnUrl="+websiteDomain+"/checkout/";
                                        }
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

			/**
			 * Process the payment and return the result
			 *
			 * @param int $order_id
			 * @return array
			 */

			public function process_payment( $order_id ) {
				try {
					$order        = wc_get_order( $order_id );
					$cart_total   = WC()->cart->total;
					$cart_total   = WC()->cart->total;
					$redeemResult = $this->redeemLoyaltyDollar( $cart_total, $order_id );
					if ( $redeemResult['result'] == true ) {
						$order->add_order_note( 'Payment done using loyalty Dollar. Transaction ID : ' . $redeemResult['transaction_id'] );
						$order->payment_complete();

						return array(
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order ),
						);
					} else {
						wc_add_notice( $redeemResult['result'], 'error' );
						return array(
							'result'  => 'failure',
							'message' => $redeemResult['message'],
						);
					}
				} catch ( Exception $exception ) {
					bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
					echo 'Got Exception : ' . esc_attr( $exception );
					return array(
						'result'  => 'failure',
						'message' => 'Unable to redeem giftcard.',
					);
				}
			}

			private function redeemLoyaltyDollar( $amount, $order_id ) {
				try {
					$balance = ( new LoyaltyDollarPaymentController() )->getLoyaltyDollarBalance();
					if ( $balance == -1 || $balance->GetCardBalanceResult->CurrentBalance < $amount ) {
						$msg = $balance == -1 ? 'Unable to check your balance' : 'Select another payment method to complete transaction.';
						wc_add_notice( $msg, 'error' );
						return array(
							'result'  => false,
							'message' => $msg,
						);
					} else {
						$result = ( new LoyaltyDollarPaymentController() )->redeemLoyaltyDollarBalance( $amount, $order_id );
						if ( $result['result'] == false ) {
							$msg = 'Unable to redeem gift card.';
							wc_add_notice( $msg, 'error' );
							return array(
								'result'  => false,
								'message' => $msg,
							);
						} else {
							return array(
								'result'         => true,
								'transaction_id' => $result['transaction_id'],
							);
						}
					}
				} catch ( Exception $e ) {
					bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				}
			}

			public function process_refund( $order_id, $amount = null, $reason = '' ) {
				try {
					$order                 = new WC_Order( $order_id );
					$bloyal_obj            = new BloyalController();
					$refund_transaction_id = $bloyal_obj->bloyal_refund_loyalty_dollars( $order_id, $amount );
					if ( $refund_transaction_id ) {
						return true;
					}
					return false;
				} catch ( Exception $e ) {
					bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				}
			}
		}
	}
}
