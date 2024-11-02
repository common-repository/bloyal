<?php


// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( 'No script!' );

add_action( 'plugins_loaded', 'bloyal_gift_card_payment_gateway' );

require_once BLOYAL_DIR . '/app/controller/bloyal_payment_controller.php';

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function bloyal_gift_card_payment_gateway() {

		class Bloyal_Gift_Card_Gateway extends WC_Payment_Gateway {
			/**
			 * Constructor for the gateway.
			 */
			public function __construct() {
				$this->domain             = 'wc-gateway-bloyal-gift-card';
				$this->id                 = 'bloyal_gift_card';
				$this->icon               = apply_filters( 'woocommerce_offline_icon', '' );
				$this->has_fields         = true;
				$this->method_title       = __( 'bLoyal Gift Card', 'wc-gateway-bloyal-gift-card' );
				$this->method_description = __( 'Allows payments thorugh bloyal gift card.', 'wc-gateway-bloyal-gift-card' );
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
							'title'   => __( 'Enable/Disable', 'wc-gateway-bloyal-gift-card' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable bLoyal Gift Card', 'wc-gateway-bloyal-gift-card' ),
							'default' => 'No',
						),
						'title'        => array(
							'title'       => __( 'Title', 'wc-gateway-bloyal-gift-card' ),
							'type'        => 'text',
							'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-bloyal-gift-card' ),
							'default'     => __( 'Gift Card', 'wc-gateway-bloyal-gift-card' ),
							'desc_tip'    => true,
						),
						'description'  => array(
							'title'       => __( 'Description', 'wc-gateway-bloyal-gift-card' ),
							'type'        => 'textarea',
							'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-bloyal-gift-card' ),
							'default'     => __( 'Amount will be deducted from your gift card balance.', 'wc-gateway-bloyal-gift-card' ),
							'desc_tip'    => true,
						),
						'validate'     => array(
							'title'       => __( 'Validate', 'wc-gateway-bloyal-gift-card' ),
							'type'        => 'button',
							'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-bloyal-gift-card' ),
							'default'     => __( '', 'wc-gateway-bloyal-gift-card' ),
							'desc_tip'    => true,
						),
						'instructions' => array(
							'title'       => __( 'Instructions', 'wc-gateway-bloyal-gift-card' ),
							'type'        => 'textarea',
							'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-bloyal-gift-card' ),
							'default'     => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-bloyal-gift-card' ),
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
				try {
					if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'on-hold' ) ) {
						echo wpautop( wptexturize( esc_attr( $this->instructions ) ) ) . PHP_EOL;
					}
				} catch ( Exception $e ) {
					bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				}
			}

			public function payment_fields() {
				try {
					if ( $description = $this->get_description() ) {
						echo wpautop( wptexturize( esc_attr( $description ) ) );
					}
				} catch ( Exception $e ) {
					bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				}

				?>
		<div id="custom_input">
			<p class="form-row form-row-wide">
				<label for="gift_card_number" class=""><?php _e( 'Gift Card Number', $this->domain ); ?></label>
				<input type="text" class="" name="gift_card_number" id="gift_card_number" placeholder="" value="" onkeyup ="isCardNumber()">
			</p>
				<?php
				$applyFullBalance = get_option( 'bloyal_apply_full_balance_giftcard' );
				if ( $applyFullBalance == 'false' ) {
					?>
			<div style="margin-top: 5px;">
				<p class="form-row form-row-wide" id="giftcard-balance-section" style="display: none;">
					<select id="select-giftcard-balance" ><option value="0">Choose An Amount</option></select>
				</p>
			</div>
				<?php } ?>
			<br/>
			<p class="form-row form-row-wide">
				<input type="button" class="" name="check_balance" id="check_balance" value="Check Balance">
				<input type="button" class="" name="apply_balance" id="apply_balance" value="Apply Balance">
				<label id="giftCardMessage" value=""></label>
			</p>
		</div>
		<script type="text/javascript">
			function isCardNumber(){
				var cardNumber = jQuery('#gift_card_number').val();
				if(cardNumber == ''){
					jQuery('#card_bal').text("Enter gift card number and place order");
				}
			}
			jQuery(document).ready(function($) {
				$('#check_balance').click(function() {
					$("#check_balance").prop('disabled', true);
					var gift_card_number = $('#gift_card_number').val();
					if ( gift_card_number == '' ) {
						$("#giftCardMessage").text('Please enter gift card number.');
						$("#check_balance").prop('disabled', false);
						return false;
					} else {
						$("#giftCardMessage").text('Checking ... ');
						var data = {
							action: 'check_gift_card_balance',
							gift_card_number: gift_card_number
						};
						$.post(ajaxurl, data, function(response) {
							var response_obj = $.parseJSON(response);
							console.log(response_obj);
							var increment_of = response_obj.apply_incrementof;
							var cartSubtotal = response_obj.cart_total;
							var availableBalance = response_obj.balance;
							if(response_obj.status == 'success'){
								//If card balance is less then order total show message ......
								if(response_obj.apply_fullbalance == "false"){
									if(availableBalance > 0){
										  $("#giftcard-balance-section").show();
										  var selectId = "#select-giftcard-balance";
										  bLoyalloyaltyOptions(availableBalance, cartSubtotal, increment_of, selectId);
									  }else {
										  $("#giftcard-balance-section").hide();
										  $("#apply_balance").prop('disabled', true);
									  }
								}
							}else {
							}
						
							$("#giftCardMessage").text(response_obj.message);
							$("#check_balance").prop('disabled', false);
							return false;
						});
					}
				});
				$('#select-giftcard-balance').on('change', function() {
					  $("#apply_balance").prop('disabled', false);
				});
				$('#apply_balance').click(function() {
					$("#check_balance").prop('disabled', true);
					$("#apply_balance").prop('disabled', true);
					var gift_card_number = $('#gift_card_number').val();
					if ( gift_card_number == '' ) {
						$("#giftCardMessage").text('Please enter gift card number.');
						$("#check_balance").prop('disabled', false);
						$("#apply_balance").prop('disabled', false);
						return false;
					} else {
						$("#giftCardMessage").text('Just a moment ... ');
							<?php if ( $applyFullBalance == 'false' ) { ?>
						 var selectedBalance = $('#select-giftcard-balance').children("option:selected").val();
						  if(selectedBalance == '0') {
							$("#giftCardMessage").text("Please choose an amount!");
							$("#apply_balance").prop('disabled', false);
							$("#check_balance").prop('disabled', false);
							 return false;
						  }
						<?php } else { ?>
							   var selectedBalance = 0;
						<?php } ?>
						var data = {
							action: 'apply_gift_card_balance',
							gift_card_number: gift_card_number,
							post_redeem_amount : selectedBalance
						};
						$.post(ajaxurl, data, function(response) {
							var response_obj = $.parseJSON(response);
							console.log(response_obj);
							if (response_obj.status == 'success'){
								//If card balance is less then order total show message ......
							} else {
							}
							$("#giftCardMessage").text(response_obj.message);
							$("#check_balance").prop('disabled', false);
							$('form.checkout').on('change', 'input[name="payment_method"]', function(){
								$(document.body).trigger('update_checkout');
							});
							// jQuery(document.body).trigger("update_checkout");
							return false;
						});
					}
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
					$order            = wc_get_order( $order_id );
					$cart_total       = WC()->cart->total;
					$gift_card_number = sanitize_text_field( $_POST['gift_card_number'] );
					if ( empty( $gift_card_number ) ) {
						$msg = 'Enter gift card number.';
						wc_add_notice( $msg, 'error' );
						return array(
							'result'  => 'failure',
							'message' => 'Enter gift card number.',
						);
					} else {
						$cart_total   = WC()->cart->total;
						$redeemResult = $this->redeemGiftCard( $gift_card_number, $cart_total, $order_id );
						if ( $redeemResult['result'] == 'success' ) {
							$order->add_order_note( 'Payment done using Gift Card. Transaction ID : ' . $redeemResult['transaction_id'] );
							$order->payment_complete();
							return array(
								'result'   => 'success',
								'redirect' => $this->get_return_url( $order ),
							);
						} else {
							wc_add_notice( $redeemResult['result'], 'error' );
							return array(
								'result'  => 'failure',
								'message' => $redeemResult['result'],
							);
						}
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

			private function redeemGiftCard( $giftCardNumber, $amount, $order_id ) {
				try {
					$balance = ( new PaymentController() )->getGiftcardBalance( $giftCardNumber );
					if ( $balance == -1 || $balance->GetCardBalanceResult->CurrentBalance < $amount ) {
						$msg = $balance == -1 ? 'Invalid gift card number. Please check gift card number.' : 'Insufficient balance on gift card.';
						wc_add_notice( $msg, 'error' );
						return array(
							'result'  => false,
							'message' => $msg,
						);
					} else {
						$isRedeem = ( new PaymentController() )->redeemGiftCardBalance( $giftCardNumber, $amount, $order_id );
						if ( $isRedeem == -1 ) {
							$msg = 'Error in payment via gift card.';
							wc_add_notice( $msg, 'error' );
							return array(
								'result'  => 'failure',
								'message' => $msg,
							);
						} else {
							return $isRedeem;
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
					$refund_transaction_id = $bloyal_obj->bloyal_refund_gift_card( $order_id, $amount );
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
