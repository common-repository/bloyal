<?php


// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( 'No script!' );

$plugin_url = BLOYAL_DIR;
$plugin_url = explode( '/plugins', $plugin_url );

add_action( 'plugins_loaded', 'bloyal_snippets_payment_gateway' );
$plugin_list = get_option( 'active_plugins' );
$epay        = false;

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

	function bloyal_snippets_payment_gateway() {

		class Bloyal_Snippets_Gateway extends WC_Payment_Gateway {

			public function __construct() {
				$this->domain             = 'wc-gateway-bloyal-snippets';
				$this->id                 = 'bloyal-snippets';
				$this->icon               = apply_filters( 'woocommerce_offline_icon', '' );
				$this->has_fields         = true;
				$this->method_title       = __( 'ADD TITLE', 'wc-gateway-bloyal-snippets' );
				$this->method_description = __( 'test description', 'wc-gateway-bloyal-snippets' );
				$this->testmode           = 'yes' === $this->get_option( 'testmode' );
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

			public function init_form_fields() {
				$this->form_fields = apply_filters(
					'wc_offline_form_fields',
					array(
						'enabled'      => array(
							'title'   => __( 'Enable/Disable', 'wc-gateway-bloyal-snippets' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable bLoyal Gift Card', 'wc-gateway-bloyal-snippets' ),
							'default' => 'No',
						),
						'title'        => array(
							'title'       => __( 'Title', 'wc-gateway-bloyal-snippets' ),
							'type'        => 'text',
							'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wc-gateway-bloyal-snippets' ),
							'default'     => __( 'Saved Payment Method', 'wc-gateway-bloyal-snippets' ),
							'desc_tip'    => true,
						),
						'description'  => array(
							'title'       => __( 'Description', 'wc-gateway-bloyal-snippets' ),
							'type'        => 'textarea',
							'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-bloyal-snippets' ),
							'default'     => __( '', 'wc-gateway-bloyal-snippets' ),
							'desc_tip'    => true,
						),
						'validate'     => array(
							'title'       => __( 'Validate', 'wc-gateway-bloyal-snippets' ),
							'type'        => 'button',
							'description' => __( 'Payment method description that the customer will see on your checkout.', 'wc-gateway-bloyal-snippets' ),
							'default'     => __( '', 'wc-gateway-bloyal-snippets' ),
							'desc_tip'    => true,
						),
						'testmode'     => array(
							'title'       => 'Test mode',
							'label'       => 'Enable Test Mode',
							'type'        => 'checkbox',
							'description' => 'Place the payment gateway in test mode using test API keys.',
							'default'     => 'yes',
							'desc_tip'    => true,
						),
						'instructions' => array(
							'title'       => __( 'Instructions', 'wc-gateway-bloyal-snippets' ),
							'type'        => 'textarea',
							'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-bloyal-snippets' ),
							'default'     => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-bloyal-snippets' ),
							'desc_tip'    => true,
						),
					)
				);
			}

			public function thankyou_page() {
				if ( $this->instructions ) {
					echo wpautop( wptexturize( esc_attr($this->instructions) ) );
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
						echo wpautop( wptexturize( esc_attr($this->instructions) ) ) . PHP_EOL;
					}
				} catch ( Exception $e ) {
					bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				}
			}

			public function payment_fields() {
				try {
					if ( $description = $this->get_description() ) {
						echo wpautop( wptexturize( esc_attr($description) ) );
					}
				} catch ( Exception $e ) {
					bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
				}
				$objectAddPaymentMethods = new AddPaymentMethods();
				$current_user            = wp_get_current_user();
				if ( ! empty( $current_user ) ) {
					$payment_methods = $objectAddPaymentMethods->save_payment_methods( $current_user->ID, $current_user->user_email );
					if ( ! empty( $payment_methods ) && isset( $payment_methods->data ) ) {
						?>
						<?php
						//print_r( $payment_methods->data );
					}
				}
			}

			public function process_payment( $order_id ) {
				try {

					$order                           = wc_get_order( $order_id );
					$cart_total                      = WC()->cart->total;
					$objepay                         = new WC_Gateway_Usaepay();
					if ( $epay ) {
						$result = $objepay->process_payment( $order_id );
						if ( ! empty( $result ) && $result['result'] == 'success' ) {
							$order->set_payment_method_title( 'test321' );
							$order->payment_complete();
							return array(
								'result'   => 'success',
								'redirect' => $this->get_return_url( $order ),
							);
						} else {
							wc_add_notice( 'Error in processing payment.', 'error' );
							return array(
								'result'  => 'failure',
								'message' => 'Error in processing payment.',
							);
						}
					} else {
						wc_add_notice( 'Error in processing payment.', 'error' );
						return array(
							'result'  => 'failure',
							'message' => 'Error in processing payment.',
						);
					}
				} catch ( Exception $exception ) {
					bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
					echo 'Got Exception : ' . esc_attr($exception);
						return array(
							'result'  => 'failure',
							'message' => 'Unable to process payment.',
						);
				}
			}
		}
	}
}
