<?php
/**
 * Plugin Name: bLoyal
 * Plugin URI:
 * Description: bLoyal provides real-time customer loyalty and omni-channel order processing to your WooCommerce web store.
 * Version: 3.1.611.33
 * Author:  bLoyal
 */

$server = '';
if ( isset( $_SERVER['QUERY_STRING'] ) ) {
	$server = sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
}

$skipArr = array( 'wc-ajax=add_to_cart' );
if ( in_array( $server, $skipArr ) ) {
	return 1;
}
defined( 'ABSPATH' ) || die( 'No script!' );
if ( ! defined( 'BLOYAL_URL' ) ) {
	define( 'BLOYAL_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'BLOYAL_DIR' ) ) {
	define( 'BLOYAL_DIR', dirname( __FILE__ ) );
}
if ( ! defined( 'BLOYAL_SNIPPETS_DIR' ) ) {
	define( 'BLOYAL_SNIPPETS_DIR', dirname( __FILE__ ) );
}
if ( ! defined( 'BLOYAL_CURL_TIME' ) ) {
	define( 'BLOYAL_CURL_TIME', 30 * 60 );
}

if ( ! defined( 'BLOYAL_UPLOAD_DIR_BASEPATH' ) ) {
	$upload_dir_obj = wp_upload_dir();
	define( 'BLOYAL_UPLOAD_DIR_BASEPATH', $upload_dir_obj['basedir']);
}

$request_uri = '';
if ( isset( $_SERVER['REQUEST_URI'] ) ) {
	$request_uri = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
}

if ( strpos( $request_uri, 'wp-admin' ) !== false ) {
	define( 'BLOYAL_CONCATENATE_SCRIPTS', false );
}
require_once BLOYAL_DIR . '/app/controller/class-bloyalcontroller.php';
require_once BLOYAL_DIR . '/app/controller/shipping_controller.php';
require_once BLOYAL_DIR . '/app/controller/custom_tax_class_controller.php';
require_once BLOYAL_DIR . '/app/controller/promotion_class_controller.php';
require_once BLOYAL_DIR . '/app/controller/class-bloyalcartcontroller.php';
require_once BLOYAL_DIR . '/app/view/bloyal_view.php';
require_once BLOYAL_DIR . '/app/controller/class-alertpopupcontroller.php';
require_once BLOYAL_DIR . '/app/controller/bloyal_resubmit_order_controller.php';
require_once BLOYAL_DIR . '/app/controller/gift_card_tender_controller.php';
require_once BLOYAL_DIR . '/app/controller/loyalty_dollar_tender_controller.php';
require_once BLOYAL_DIR . '/app/controller/bloyal_logger_service.php';
require_once BLOYAL_DIR . '/app/controller/multiple_shipping_address.php';
require_once BLOYAL_DIR . '/app/controller/class-add-payment-methods.php';
require_once BLOYAL_DIR . '/app/controller/bloyal_snippets_payment.php';
require_once BLOYAL_DIR . '/app/controller/class-bloyalstoredpaymentmethod.php';
require_once BLOYAL_SNIPPETS_DIR . '/app/controller/bloyal_snippets_controller.php';
require_once BLOYAL_SNIPPETS_DIR . '/app/controller/bloyal_snippets_master_settings_controller.php';
require_once BLOYAL_SNIPPETS_DIR . '/app/view/bloyal_snippets_view.php';
require_once BLOYAL_SNIPPETS_DIR . '/app/controller/bloyal_snippets_logger_controller.php';
require_once BLOYAL_DIR . '/app/controller/class-paymentalertcontroller.php';
require_once BLOYAL_DIR . '/app/controller/bloyal_loyalty_dollar_payment_controller.php';


$bloyal_obj        = new BloyalController();
$payment_alert_obj = new BLOYAL_PaymentAlertController();

register_activation_hook( __FILE__, 'bloyal_activation' );
function bloyal_activation() {
	try {
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && current_user_can( 'activate_plugins' ) ) {
			wp_die( esc_html( 'This plugin requires the WooCommerce Plugin to be installed and active.<br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>' ) );
		}
		update_option( 'loyalty_block', "true" );
		update_option( 'bloyal_click_and_collect_status', "2" );
		update_option( 'bloyal_apply_full_balance_loyalty', "false" );
		update_option( 'bloyal_apply_full_balance_giftcard', "false" );
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

register_deactivation_hook( __FILE__, 'bloyal_deactivation' );
function bloyal_deactivation() {

}

register_uninstall_hook( __FILE__, 'bloyal_uninstall' );
function bloyal_uninstall(){
	try {
		delete_option( 'loyalty_block' );
		delete_option( 'bloyal_click_and_collect_status');
		delete_option( 'bloyal_apply_full_balance_loyalty');
		delete_option( 'bloyal_apply_full_balance_giftcard');
		delete_option( 'bloyal_Tenders');
		delete_option( 'bloyal_access_key');
		delete_option( 'bloyal_ShippingCarriers');
		delete_option( 'bloyal_custom_grid_api_url');
		delete_option( 'bloyal_custom_loyaltyengine_api_url');
		delete_option( 'bloyal_custom_orderengine_api_url');
		delete_option( 'bloyal_custompayment_api_url');
		delete_option( 'bloyal_custom_logging_api_url');
		delete_option( 'bloyal_apply_in_increment_of_giftcard');
		delete_option( 'bloyal_apply_in_increment_of_loyalty');
		delete_option( 'bloyal_log_enable_disable');
		delete_option( 'bloyal_snippet_code');
		delete_option( 'bloyal_snippet_informational_code');
		delete_option( 'bloyal_snippet_confirmation_code');
		delete_option( 'bloyal_snippet_problem_code');
		delete_option( 'bloyal_tender_payments_mapping');
		delete_option( 'is_bloyal_custom_api_url');
		delete_option( 'bloyal_access_key_verification');
		delete_option( 'bloyal_domain_url');
		delete_option( 'bloyal_on_account_tender_code');
		delete_option( 'bloyal_snippets_codes');
		delete_option( 'bloyal_saved_device_snippets_lists');
		delete_option( 'Device_Code');
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'wp_ajax_view_loyalty_block', 'bloyal_fetch_discount_summary' );

add_filter( 'woocommerce_default_address_fields', 'bloyal_wdm_override_default_address_fields' );
function bloyal_wdm_override_default_address_fields( $address_fields ) {
	$query_string = null;
	if ( isset( $_SERVER['QUERY_STRING'] ) ) {
		$query_string = sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
	}
	if ( ! ( is_checkout() && ! $query_string ) ) {
		return $address_fields;
	} else {
		$isDisplayDOB  = get_option( 'bloyal_display_DOB' );
		$isRequiredDOB = get_option( 'bloyal_required_DOB' );
		if ( empty( $isDisplayDOB ) || false == $isDisplayDOB ) {
			bLoyalLoggerService::write_custom_log( "\r\n ========== bloyal_display_DOB ============\r\n" . $isDisplayDOB );
		}
		if ( 'true' == $isDisplayDOB ) {
			$address_fields = bloyal_add_birth_date_address_field( $address_fields, $isRequiredDOB );
		}
		return $address_fields;
	}
}

function bloyal_add_birth_date_address_field( $address_fields, $isRequiredDOB ) {
	if ( empty( $isRequiredDOB ) || false == $isRequiredDOB ) {
		bLoyalLoggerService::write_custom_log( "\r\n ========== bloyal_required_DOB ============\r\n" . $isRequiredDOB );
	}
	$user_id         = get_current_user_id();
	if(!empty($user_id)) {
		$user_birth_date = get_user_meta( $user_id, 'birth_date', true );
	} else {
		$user_birth_date = WC()->session->get( 'guest_user_birth_date' );
	}

	$address_fields['birth_date'] = array(
		'label'       => __( 'Birth Date', 'woocommerce' ),
		'required'    => 'true' == $isRequiredDOB ? true : false,
		'class'       => array( 'form-row-wide' ),
		'type'        => 'date',
		'placeholder' => 'MM/DD/YYYY',
		'priority'    => 100,
		'default'     => $user_birth_date,
	);
	
	return $address_fields;

}

add_action( 'show_user_profile', 'bloyal_extra_user_profile_fields' );
add_action( 'edit_user_profile', 'bloyal_extra_user_profile_fields' );

function bloyal_extra_user_profile_fields( $user ) {
	?>
	<table class="form-table">
		<tr>
			<th><label for="birth_date"><?php esc_html_e( 'Date Of Birth' ); ?></label></th>
			<td>
				<input type="text" required="true" name="birth_date" id="birth_date" value="<?php echo esc_attr( get_the_author_meta( 'birth_date', $user->ID ) ); ?>" class="regular-text" /><br />
				<span class="description"><?php esc_html_e( 'Please enter your birth date.' ); ?></span>
			</td>
		</tr>
	</table>
	<?php
}

add_action( 'personal_options_update', 'bloyal_save_extra_user_profile_fields' );
add_action( 'edit_user_profile_update', 'bloyal_save_extra_user_profile_fields' );
function bloyal_save_extra_user_profile_fields( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}
	$birth_date = sanitize_text_field( wp_unslash(  $_POST['birth_date'] ) );
	if ( isset( $_POST['birth_date'] ) ) {
		
		update_user_meta( $user_id, 'birth_date', $birth_date );
	}
}

add_filter( 'woocommerce_rate_label', 'bloyal_override_woocom_tax_rate_lable', 10, 2 );
function bloyal_override_woocom_tax_rate_lable( $rate_lable, $key ) {
	return 'Taxes';
}

add_filter( 'cron_schedules', 'bloyal_set_bloyal_curl_time' );
function bloyal_set_bloyal_curl_time( $schedules ) {
	try {
		$schedules = bloyal_define_bloyal_curl_time_schedule( $schedules );
		return $schedules;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_define_bloyal_curl_time_schedule( $schedules ) {
	if ( ! isset( $schedules['bLoyal_curl_time'] ) ) {
		$schedules['bLoyal_curl_time'] = array(
			'interval' => BLOYAL_CURL_TIME,
			'display'  => __( 'Once every 30 minutes' ),
		);
	}
	return $schedules;
}

add_action( 'woocommerce_thankyou', 'bloyal_action_woocommerce_thankyou', 10, 1 );
function bloyal_action_woocommerce_thankyou( $order_get_id ) {
	try {
		$bloyal_obj                       = new BloyalController();
		$objectPromotionController        = new PromotionClassController();
		$objectMultipleShippingController = new BLOYAL_MultipleShippingAddressController();
		$check_save_shipping_address      = WC()->session->get( 'check_save_shipping_address' );
		if ( 'true' == $check_save_shipping_address ) {
			bloyal_save_shipping_addresses( $order_get_id );
		}
		$objectPromotionController->bloyal_commit_cart( $order_get_id, $bloyal_obj );
		WC()->session->set( 'bloyal_cart_data', null ); // removing bloyal cart session data
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_save_shipping_addresses( $order_get_id ) {
	$objectMultipleShippingController = new BLOYAL_MultipleShippingAddressController();
	$current_user                     = wp_get_current_user();
	if ( ! empty( $current_user ) ) {
		$multipleShippingResponse = $objectMultipleShippingController->save_multiple_shipping_addresses( $order_get_id );
	}
}

add_action( 'woocommerce_after_checkout_validation', 'bloyal_after_checkout_validation', 10, 2 );
function bloyal_after_checkout_validation( $data, $errors ) {
	try {
		$selected_payment_method_id = WC()->session->get( 'chosen_payment_method' );
		// check validation choose giftcard balance from giftcard payment method
		if ( $selected_payment_method_id == 'bloyal_gift_card' ) {
			$applyFullBalance = get_option( 'bloyal_apply_full_balance_giftcard' );
			if ( $applyFullBalance == 'false' ) {
				$gift_card_amount = WC()->session->get( 'partial_gift_amount_redeem' );
				$gift_card_number = WC()->session->get( 'partial_gift_number_increment_of' );

				if ( is_null( $gift_card_number ) ) {
					$msg = 'Please enter gift card number!';
					$errors->add( 'custom_error', __( $msg ) );
					return $errors;
				} elseif ( is_null( $gift_card_amount ) ) {
					$msg = 'Please select and apply gift balance amount!';
					$errors->add( 'custom_error', __( $msg ) );
					return $errors;
				}
			}
		}
		// check validation choose loyalty balance from loyalty payment method
		if ( $selected_payment_method_id == 'bloyal_loyalty_dollar' ) {
			$applyFullBalance = get_option( 'bloyal_apply_full_balance_loyalty' );
			if ( $applyFullBalance == 'false' ) {
				$loyalty_dollar_amount = WC()->session->get( 'partial_loyalty_amount_redeem' );
				if ( is_null( $loyalty_dollar_amount ) ) {
					$msg = 'Please select and apply loyalty balance amount!';
					$errors->add( 'custom_error', __( $msg ) );
					return $errors;
				}
			} else {
				$loyalty_balance_check = WC()->session->get( 'loyalty_balance_check' );
				if ( is_null( $loyalty_balance_check ) ) {
					$msg = 'Please click on check balance!';
					$errors->add( 'custom_error', __( $msg ) );
					return $errors;
				}
			}
		}

		$isDisplayDOB        = get_option( 'bloyal_display_DOB' );
		$isRequiredDOB       = get_option( 'bloyal_required_DOB' );
		$birth_date 	     = sanitize_text_field( $_REQUEST['billing_birth_date'] );
 		$shipping_birth_date = sanitize_text_field( $_REQUEST['shipping_birth_date'] );
		$guest_user_data = $data;
		WC()->session->set( 'ship_to_different_address', $guest_user_data['ship_to_different_address'] ? true : false );
		if ( 'true' == $isDisplayDOB ) {
			$errors = bloyal_validate_dob( $birth_date, $shipping_birth_date, $errors );
		}
		if ( 'true' == $isRequiredDOB ) {
			$errors = bloyal_verify_required_dob( $birth_date, $shipping_birth_date, $errors, $guest_user_data );
		}
		$num_errors                          = sizeof( $errors->errors );
		$guest_user_data['billing']['phone'] = $data['billing_phone'];
		$guest_user_data['billing']['email'] = $data['billing_email'];
		foreach ( array( 'first_name', 'last_name', 'company', 'country', 'address_1', 'address_2', 'city', 'state', 'postcode' ) as $value ) {
			$guest_user_data['billing'][ $value ]  = $data[ 'billing_' . $value ];
			$guest_user_data['shipping'][ $value ] = $data[ 'shipping_' . $value ];
		}

		if ( is_user_logged_in() ) {
			$user_info                                = wp_get_current_user();
			$guest_user_data['billing']['email']      = $user_info->user_email;
			$guest_user_data['billing']['first_name'] = $user_info->user_firstname;
			$guest_user_data['billing']['last_name']  = $user_info->user_lastname;
		}
		WC()->session->set( 'guest_user_data', $guest_user_data );
		$arrAlertData = WC()->session->get( 'bloyal_alerts_data' );
		$bloyal_approve_cart = bloyal_approve_cart();
		if ( ! empty( $arrAlertData ) ) {
			$checkout_page_id = wc_get_page_id( 'checkout' );
			$checkout_page_url = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';
			bloyal_collect_errors_list( $bloyal_approve_cart, $errors );
			//$errors->add( 'custom_error', __( 'We are unable to process your order.  Please confirm alerts dialog box. <a href="'.$checkout_page_url.'" >Click</a> to confirm.' ) );
			return;
		}
		
		//Check whether access key is not empty.
		$access_key          = get_option('bloyal_access_key');
		if ( !empty($access_key ) ) {
			if ( ! $bloyal_approve_cart['status'] ) {
				bloyal_collect_errors_list( $bloyal_approve_cart, $errors );
				return;
			}
	    } 
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

 function isValid($date, $format = 'm/d/Y'){
    $dt = DateTime::createFromFormat($format, $date);
    return $dt && $dt->format($format) === $date;
  }


function bloyal_collect_errors_list( $bloyal_approve_cart, $errors ) {
	$arrAlertData = WC()->session->get( 'bloyal_cart_alerts_data' );
	if(!empty($bloyal_approve_cart['alert'])) {
		foreach ( $bloyal_approve_cart['alert'] as $key => $value ) {
			if(!empty($value) && ($value->Category == 'Problem') ) {
				$errors->add( 'custom_error', __( $value->Message[0] ) );
			}else {
				if ( ! empty( $bloyal_approve_cart['alert'] ) ) {
					$errors->add( 'custom_error', __( 'We are unable to process your order.  Please confirm alerts dialog box. <a href="/checkout" >Click</a> to confirm.' ) );
				}
			}	
		}
	}else {
		if ( ! empty( $arrAlertData ) ) {
			$errors->add( 'custom_error', __( 'We are unable to process your order.  Please confirm alerts dialog box. <a href="/checkout" >Click</a> to confirm.' ) );
		}
	}
}

function bloyal_validate_dob( $birth_date, $shipping_birth_date, $errors ) {
	if ( ! empty( $birth_date ) ) {
		$date_format = explode( '/', $birth_date );
		$count_date  = count( $date_format );
		if ( 3 == $count_date ) {
			if ( (int) $date_format[0] > 12 || 0 == (int) $date_format[0] ) {
				$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
				return $errors;
			}
			if ( (int) $date_format[1] > 31 || 0 == (int) $date_format[1] ) {
				$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
				return $errors;
			}
			if ( 0 == (int) $date_format[2] ) {
				$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
				return $errors;
			}
			$startDate = strtotime(date('m/d/Y', strtotime($birth_date) ) );
    		$currentDate = strtotime(date("m/d/Y"));
			if($startDate > $currentDate) {
				$errors->add( 'custom_error', __( 'Future<strong> Date Of Birth</strong> is not accepted.' ) );
				return $errors;
			}
			$start_shipping_date = strtotime(date('m/d/Y', strtotime($shipping_birth_date) ) );
			if($start_shipping_date > $currentDate) {
				$errors->add( 'custom_error', __( 'Future<strong> Date Of Birth</strong> is not accepted.' ) );
				return $errors;
			}
		}
		
	}
	if ( ! empty( $birth_date ) && preg_match( '/[a-z]/i', $birth_date ) ) {
		$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
		return $errors;
	}
	if ( ! empty( $shipping_birth_date ) && preg_match( '/[a-z]/i', $shipping_birth_date ) ) {
		$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
		return $errors;
	}
	return $errors;
}

function bloyal_verify_required_dob( $birth_date, $shipping_birth_date, $errors, $guest_user_data ) {
	if ( preg_match( '/[a-z]/i', $birth_date ) ) {
		$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
		return $errors;
	}
	if ( empty( $birth_date ) || '' == $birth_date || null == $birth_date ) {
		$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is a required field.' ) );
		return $errors;
	}
	if ( $guest_user_data['ship_to_different_address'] ) {
		if ( empty( $shipping_birth_date ) || '' == $shipping_birth_date || null == $shipping_birth_date ) {
			$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is a required field.' ) );
			return $errors;
		}
		if ( preg_match( '/[a-z]/i', $shipping_birth_date ) ) {
			$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
			return $errors;
		}
		$date_format_shipping = explode( '/', $shipping_birth_date );
		$count_date_shipping  = count( $date_format_shipping );
		if ( 3 == $count_date_shipping ) {
			if ( (int) $date_format_shipping[0] > 12 || 0 == (int) $date_format_shipping[0] ) {
				$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
				return $errors;
			}
			if ( (int) $date_format_shipping[1] > 31 || 0 == (int) $date_format_shipping[1] ) {
				$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
				return $errors;
			}
			if ( 0 == (int) $date_format_shipping[2] ) {
				$errors->add( 'custom_error', __( '<strong>Date Of Birth</strong> is not a valid Date.' ) );
				return $errors;
			}
		}
	}
	return $errors;
}

function bloyal_approve_cart( $order_id = null ) {
	try {
		$bloyal_obj = new BloyalController();
		$obj_alert  = new BLOYAL_AlertPopUpController();
		$obj_alert->process_alert_data( array(), 'false' );
		$third_party_cart_uid = WC()->session->get( 'third_party_cardId' );
		$cart_uid             = ( null != $third_party_cart_uid && '' != $third_party_cart_uid ) ? $third_party_cart_uid : $bloyal_obj->get_uid();
		$cart_data            = WC()->session->get( 'bloyal_cart_data', null );
		$cart                 = '';
		$cart_lines           = $cart_data->Cart->Lines;
		if ( $cart_uid == $cart_data->Cart->Uid ) {
			$cart = $cart_data->Cart;
		}
			$externally_applied = $cart->Shipments;
		if ( $cart_uid ) {
			$result = bloyal_approve_cart_result( $order_id, $cart_uid, $cart, $bloyal_obj, $obj_alert );
			return $result;
		}
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_approve_cart_result( $order_id, $cart_uid, $cart, $bloyal_obj, $obj_alert ) {
	$order        = new WC_Order( $order_id );
	$cart_request = array(
		'Cart' => $cart,
	);
	bLoyalLoggerService::write_custom_log( "\n\r==========Approve Call============\r\n" . json_encode( $cart_request ) );
	$result = $bloyal_obj->send_curl_request( $cart_request, 'carts/commands/approve', 'loyaltyengine', 1 );
	bLoyalLoggerService::write_custom_log( "\n\r==========Approve response============ \r\n" . json_encode( $result ) );
	if ( isset( $result->status ) && 'success' == $result->status ) {
		$bLoyalApproveAlerts = $result->data->Alerts;
		if ( ! empty( $bLoyalApproveAlerts ) ) {
			$obj_alert->process_alert_data( $bLoyalApproveAlerts, 'false' );
		}
	}
	return array(
		'status' => $result->data->Approved,
		'alert'  => $result->data->Alerts,
	);
}

add_filter( 'woocommerce_order_item_get_formatted_meta_data', 'bloyal_wc_get_formatted_meta_data', 10, 1 );
function bloyal_wc_get_formatted_meta_data( $formatted_meta ) {
	$time_pre = microtime( true );
	try {
		$temp_metas = array();
		foreach ( $formatted_meta as $key => $meta ) {
			if ( isset( $meta->key ) ) {
				$meta               = bloyal_define_temp_metas( $formatted_meta, $key, $meta );
				$temp_metas[ $key ] = $meta;
			}
		}
		$time_post = microtime( true );
		$exec_time = $time_post - $time_pre;
		return $temp_metas;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_define_temp_metas( $formatted_meta, $key, $meta ) {
	$meta->display_key = str_replace( '_', ' ', $meta->key );
	if ( in_array(
		$meta->key,
		array(
			'_bloyal_sale_price_discount',
			'_bloyal_item_level_discount',
			'_bloyal_order_level_discount',
			'_bloyal_external_discount',
			'_original_price',
		)
	) ) {
		$meta->display_value = wc_price( $meta->value, array() );
	}
	return $meta;
}

add_action( 'woocommerce_admin_order_totals_after_tax', 'bloyal_custom_admin_order_totals_after_tax', 10, 1 );
function bloyal_custom_admin_order_totals_after_tax( $order_id ) {
	try {
		$loyalty_dollar_total = get_post_meta( $order_id, '_bloyal_loyalty_dollar_total', true );
		$gift_card_total      = get_post_meta( $order_id, '_bloyal_gift_card_total', true );
		if ( ! empty( $loyalty_dollar_total ) ) {
			bloyal_echo_bloyal_discount_totals( 'Loyalty Dollar Total', -$loyalty_dollar_total );
		}
		if ( ! empty( $gift_card_total ) ) {
			bloyal_echo_bloyal_discount_totals( 'Gift Card Total', -$gift_card_total );
		}
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_echo_bloyal_discount_totals( $discount_title, $discount_total ) {
	echo(
	'<tr>
		<td class="label">' . $discount_title . ':</td>
		<td width="1%"></td>
		<td class="custom-total">' . wc_price( $discount_total, array() ) . '</td>
	</tr>' );
}

function bloyal_change_cart_product_price( $price, $cart_item, $item_key ) {
	try {
		$regular_price = $cart_item['data']->get_regular_price();
		$cart_price    = $cart_item['data']->get_price();
		if ( $regular_price != $cart_price ) {
			return '<del>$' . $regular_price . '</del> ' . $price;
		}
		return $price;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	function bloyal_shipping_method() {
		try {
			if ( ! class_exists( 'bloyal_Shipping_Method' ) ) {
				class bloyal_Shipping_Method extends WC_Shipping_Method {
					/**
					 * Constructor for your shipping class
					 *
					 * @access public
					 * @return void
					 */
					public function __construct() {
						$this->id                 = 'bloyal';
						$this->method_title       = __( 'bloyal Shipping', 'bloyal' );
						$this->method_description = __( 'Custom Shipping Method for bloyal', 'bloyal' );
					}
					/**
					 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
					 *
					 * @access public
					 * @param mixed $package
					 * @return void
					 */
					public function calculate_shipping( $package = array() ) {
						try {
							$bloyal_obj    = new BloyalController();
							$configuration = json_decode( $bloyal_obj->bloyal_get_configuration_details_from_wpdb() );
							if ( 'false' == $configuration->applied_shipping_charges ) {
								return;
							}
							$shipping_obj = new ShippingController();
							$rates        = $shipping_obj->calculateShipping( $package );
							foreach ( $rates as $rate ) {
								$this->add_rate( $rate );
							}
							WC()->session->set( 'custom_bloyal_shipping_rates', $rates );
						} catch ( Exception $ex ) {
							bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
						}
					}
				}
			}
		} catch ( Exception $ex ) {
			bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
		}
	}
	add_action( 'woocommerce_shipping_init', 'bloyal_shipping_method' );
	function bloyal_add_bloyal_shipping_method( $methods ) {
		$methods[] = 'bloyal_shipping_method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'bloyal_add_bloyal_shipping_method' );
}

function bloyal_shipping_tax_rate( $rate, $wc_shipping_object ) {
	try {
		$rates = WC()->session->get( 'applied_shipping_taxes' );
		return $rates;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

/**
 * Override the WooCommerce variation product tax class with the new bLoyal tax class
 *
 * @param String               $tax_class
 * @param WC_Variation_Product $product
 *
 * @return string
 */

function bloyal_override_variation_product_tax_class( $tax_class, $product ) {
	try {
		$tax_class = WC()->session->get( 'applied_line_taxes' );
		return $tax_class[ $product->get_id() ];
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

/**
 * Override the WooCommerce product tax class with the new Taxamo tax class
 *
 * @param String     $tax_class
 * @param WC_Product $product
 *
 * @return string
 */

function bloyal_override_product_tax_class( $tax_class, $product ) {
	try {
		$tax_class = WC()->session->get( 'applied_line_taxes' );
		return $tax_class[ $product->get_id() ];
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'woocommerce_before_calculate_totals', 'bloyal_calculate_bloyal_promotions', 20, 2 );

function bloyal_calculate_bloyal_promotions( $cart_object, $order_id = 0 ) {
	try {
		if ( isset( $_REQUEST['add-to-cart'] ) ) {
			return;
		}
		global $woocommerce, $wpdb;
		$objectBloyalCarController = new BloyalCartController();
		$objectPromotionController = new PromotionClassController();
		$bloyal_obj                = new BloyalController();
		$objectShippingController  = new ShippingController();
		WC()->session->set( 'bloyal_cart_data', null );
		$is_bloyal_applied_taxes            = get_option( 'bloyal_applied_taxes' );
		$is_bloyal_applied_shipping_charges = get_option( 'bloyal_applied_shipping_charges' );
		$third_party_cart_uid               = WC()->session->get( 'third_party_cardId' );
		$cart_uid                           = ( null != $third_party_cart_uid && '' != $third_party_cart_uid ) ? $third_party_cart_uid : $bloyal_obj->get_uid();
		$bloyalCart['Cart']                 = $objectBloyalCarController->make_bloyal_cart( $cart_object, $cart_uid, $is_bloyal_applied_taxes, $order_id );
		$methods                            = WC()->session->get( 'chosen_shipping_methods' );
		$external                           = $bloyalCart['Cart']['Shipments'][0]['ExternallyAppliedCharge'];
		$activeMethod                       = isset( $methods[0] ) ? $methods[0] : '';
		if ( $external ) {
			$is_virtual_porduct_order = WC()->session->get( 'is_virtual_porduct_order' );
			$session_used             = 0;
			$bloyalSession            = $bloyal_obj->getSessionData();
			if ( ! empty( $bloyalSession ) ) {
				$rate_id = isset( $bloyalSession['rate_id'] ) ? $bloyalSession['rate_id'] : '';
				$charge  = isset( $bloyalSession['cost'] ) ? $bloyalSession['cost'] : '';
				if ( ( $rate_id == $activeMethod ) && $charge ) {
					$bloyalCart['Cart']['Shipments'][0]['Charge'] = $is_virtual_porduct_order ? 0 : $charge;
					$session_used                                 = 1;
				}
			}
			if ( ! $session_used ) {
				foreach ( WC()->shipping->get_packages() as $i => $package ) {
					if ( ! isset( $methods[ $i ], $package['rates'][ $methods[ $i ] ] ) ) {
						$not = 1;
					} else {
						$selected     = $package['rates'][ $methods[ $i ] ];
						$cost         = $selected->cost;
						$activeMethod = $selected->id;
						$bloyalCart['Cart']['Shipments'][0]['Charge'] = $is_virtual_porduct_order ? 0 : $cost;
					}
				}
			}
		}
		bLoyalLoggerService::write_custom_log( "\r\n ========== Request calculate call ============\r\n" . json_encode( $bloyalCart['Cart'] ) );
		$bloyal_cart_object = $bloyal_obj->send_curl_request( $bloyalCart, 'carts/commands/calculates', 'loyaltyengine', 1 );

		

		bLoyalLoggerService::write_custom_log( "\r\n ==========Response calculate call============\r\n" . json_encode( $bloyal_cart_object ) . "\r\n---------------\r\n" );

		if ( isset( $bloyal_cart_object->data ) ) {
			$bloyal_shipping_services['CarrierCode'] = $bloyal_cart_object->data->Cart->Shipments[0]->CarrierCode;
			$bloyal_shipping_services['ServiceCode'] = $bloyal_cart_object->data->Cart->Shipments[0]->ServiceCode;
			$bloyal_shipping_services['isGuest']     = $bloyal_cart_object->data->Cart->GuestCheckout;
			WC()->session->set( 'bloyal_shipping_services', $bloyal_shipping_services );
			WC()->session->set( 'bloyal_cart_data', $bloyal_cart_object->data );
			$session_bloyal_coupons = WC()->session->get( 'bloyal_coupon' );
			if ( ! empty( $bloyal_cart_object->data->LoyaltySummary->AppliedCoupons ) || ! empty( $session_bloyal_coupons ) ) {
				foreach ( $bloyal_cart_object->data->LoyaltySummary->AppliedCoupons as $key => $value ) {
					if ( ! $value->Redeemed ) {
						foreach ( $session_bloyal_coupons as $session_bloyal_coupons_key => $session_bloyal_coupons_value ) {
							if ( strtoupper( $session_bloyal_coupons_value['coupon_code'] ) == $value->Code ) {
								$session_bloyal_coupons[ $session_bloyal_coupons_key ]['flag'] = true;
								if ( ! $session_bloyal_coupons_value['flag'] ) {
									wc_add_notice( "Coupon Code '" . $value->Code . "' is not valid for this order. ", 'error' );
									unset( $session_bloyal_coupons[ $session_bloyal_coupons_key ] );
									WC()->session->set( 'bloyal_coupon', $session_bloyal_coupons );
								}
							}
						}
					} else {
						if ( ! empty( $session_bloyal_coupons ) ) {
							foreach ( $session_bloyal_coupons as $session_bloyal_coupons_key => $session_bloyal_coupons_value ) {
								$session_bloyal_coupons[ $session_bloyal_coupons_key ]['flag'] = true;
								if ( ! $session_bloyal_coupons_value['flag'] ) {
									wc_add_notice( 'Coupon code applied successfully', 'success' );
									WC()->session->set( 'bloyal_coupon', $session_bloyal_coupons );
								}
							}
						}
					}
				}
			}
			if ( 'error' == $bloyal_cart_object->status ) {
				$msg = $bloyal_cart_object->message;
				if ( ! wc_has_notice( $msg, 'error' ) && is_page( 'checkout' ) ) {
					wc_add_notice( $msg, 'error' );
				}
			}
			$u_id = isset( $bloyal_cart_object->data->Cart->Uid ) ? $bloyal_cart_object->data->Cart->Uid : '';
			if ( $u_id && ! $cart_uid ) {
				$woo_session_id = WC()->session->get_customer_id();
				// echo "<pre>";
				// print_r(WC()->session);
				WC()->session->set(
					'bloyal_uid',
					array(
						'u_id'       => $u_id,
						'session_id' => $woo_session_id,
					)
				);
			}
			$obj_alert    = new BLOYAL_AlertPopUpController();
			$bloyalAlerts = $bloyal_cart_object->data->LoyaltySummary->Alerts;
			WC()->session->set( 'bloyal_cart_alerts_data', $bloyalAlerts );
			$obj_alert->process_alert_data( $bloyalAlerts, 'true' );
			$objectPromotionController->add_promotions( $cart_object, $bloyal_cart_object );
			$discount = $bloyal_cart_object->data->Cart->Shipments[0]->Discount;
			$cost     = $bloyal_cart_object->data->Cart->Shipments[0]->Charge;
			if ( $discount && $external ) {
				WC()->session->set( 'bloyal_shipping_cart_data', $bloyal_cart_object->data->Cart->Shipments );
			} else {
				WC()->session->set( 'bloyal_shipping_cart_data', null );
			}
			if ( $activeMethod ) {
				$woo_session_id = WC()->session->get_customer_id();
				$final_price    = $cost - $discount;
				if ( $final_price < 0 ) {
					$final_price = 0;
				}

				WC()->session->set(
					'bloyal_shipping_rate_cost',
					array(
						'rate_id'     => $activeMethod,
						'cost'        => $cost,
						'final_price' => $final_price,
						'session_id'  => $woo_session_id,
						'discount'    => $discount,
						'external'    => $external,
					)
				);
				if ( 'false' == $is_bloyal_applied_taxes ) {
					$objectShippingController->change_wooc_shipping_tax_amount(
						array(
							'shipping_discount'      => $discount,
							'shipping_original_cost' => $cost,
						)
					);
				}
				add_filter( 'woocommerce_shipping_rate_cost', 'bloyal_change_shiping_rate_cost', 10, 2 );
				add_filter( 'woocommerce_shipping_rate_label', 'bloyal_change_shiping_rate_label', 10, 2 );
			} else {
				WC()->session->set( 'bloyal_shipping_rate_cost', null );
			}
			if ( 'false' != $is_bloyal_applied_taxes ) {
				$objectCustomTaxController = new CustomTaxClassController();
				$objectCustomTaxController->calculate_and_apply_custom_tax( $cart_object, $bloyal_cart_object );
			}
		}
		if ( 'error' == $bloyal_cart_object->status ) {
			$msg = $bloyal_cart_object->message;
			wc_clear_notices();
			if ( ! wc_has_notice( $msg, 'error' ) && 'wc-ajax=checkout' == $_SERVER['QUERY_STRING'] ) {
				wc_add_notice( $msg, 'error' );
			}
		}
		WC()->session->set( 'bloyal_calculated', true );
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}




function bloyal_change_shiping_rate_cost( $cost, $rate ) {
	try {
		$bloyal_obj    = new BloyalController();
		$bloyalSession = $bloyal_obj->getSessionData();
		if ( ! empty( $bloyalSession ) ) {
			$rate_id     = isset( $bloyalSession['rate_id'] ) ? $bloyalSession['rate_id'] : '';
			$final_price = isset( $bloyalSession['final_price'] ) ? $bloyalSession['final_price'] : '0';
			$discount    = isset( $bloyalSession['discount'] ) ? $bloyalSession['discount'] : '0';
			if ( ( $rate_id == $rate->id ) ) {
				$external = isset( $bloyalSession['external'] ) ? $bloyalSession['external'] : '';
				if ( $external ) {
					$bloyalSession['cost'] = $cost;
					WC()->session->set( 'bloyal_shipping_rate_cost', $bloyalSession );
					$final_price = $cost - $discount;
					if ( $final_price < 0 ) {
						$final_price = 0;
					}
				}
				return $final_price > 0 ? $final_price : 0;
			}
		}
		return $cost;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_change_shiping_rate_label( $label, $rate ) {
	try {
		$bloyal_obj    = new BloyalController();
		$cost          = $rate->cost;
		$bloyalSession = $bloyal_obj->getSessionData();
		if ( ! empty( $bloyalSession ) ) {
			$label = bloyal_set_rate_label( $bloyalSession, $label, $rate, $cost );
		}
		return $label;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_filter( 'woocommerce_cart_shipping_method_full_label', 'bloyal_add_free_shipping_label', 10, 2 );
function bloyal_add_free_shipping_label( $label, $method ) {
	if ( 0 == $method->cost && ! ( strpos( $label, ': $0.00' ) ) ) {
		$label .= ': $0.00';
	}
	return $label;
}

function bloyal_set_rate_label( $bloyalSession, $label, $rate, $cost ) {
	$rate_id     = isset( $bloyalSession['rate_id'] ) ? $bloyalSession['rate_id'] : '';
	$charge      = isset( $bloyalSession['cost'] ) ? $bloyalSession['cost'] : '';
	$discount    = isset( $bloyalSession['discount'] ) ? $bloyalSession['discount'] : '';
	$final_price = isset( $bloyalSession['final_price'] ) ? $bloyalSession['final_price'] : '';
	if ( ( $rate_id == $rate->id ) ) {
		$label = bloyal_update_label_price( $label, $cost, $charge, $final_price );
	}
	return $label;
}

add_action( 'woocommerce_cart_calculate_fees', 'bloyal_apply_split_tenders_to_cart' );
function bloyal_apply_split_tenders_to_cart() {
	global $woocommerce;
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	$gift_card_amount = WC()->session->get( 'partial_gift_amount' );
	if ( ( is_float( $gift_card_amount ) ) && ( $gift_card_amount > 0 ) && ( ! is_null( $gift_card_amount ) ) ) {
		$woocommerce->cart->add_fee( 'Gift Card', -$gift_card_amount, false );
		add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', 'bloyal_exlude_cart_fees_taxes', 10, 3 );
		add_filter( 'woocommerce_available_payment_gateways', 'bloyal_unset_gift_card_method', 1 );
	}
	$loyalty_dollar_amount = WC()->session->get( 'partial_loyalty_amount' );
	if ( ( is_float( $loyalty_dollar_amount ) ) && ( $loyalty_dollar_amount > 0 ) && ( ! is_null( $loyalty_dollar_amount ) ) ) {
		$woocommerce->cart->add_fee( 'Loyalty Dollars', -$loyalty_dollar_amount, false );
		add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', 'bloyal_exlude_cart_fees_taxes', 10, 3 );
		add_filter( 'woocommerce_available_payment_gateways', 'bloyal_unset_loyalty_method', 1 );
	}
}

function bloyal_unset_gift_card_method( $gateways ) {
	unset( $gateways['bloyal_gift_card'] );
	return $gateways;
}

function bloyal_unset_loyalty_method( $gateways ) {
	unset( $gateways['bloyal_loyalty_dollar'] );
	return $gateways;
}

function bloyal_exlude_cart_fees_taxes( $taxes, $fee, $cart ) {
	return array();
}

function bloyal_update_label_price( $label, $cost, $charge, $final_price ) {
	if ( $cost != $charge ) {
		if ( $final_price ) {
			$label .= ' <del>$' . $charge . '</del>';
		} else {
			$label .= ' <del>$' . $charge . '</del>: $0.00';
		}
	} else {
		if ( ! $cost ) {
			$is_free = strcasecmp( $label, 'Free Shipping' );
			if ( $is_free ) {
				//$label .= ' <del>$' . $charge . '</del>: $0.00';
				$label .= '';
			}
		}
	}
	return $label;
}

add_filter( 'woocommerce_product_needs_shipping', 'bloyal_wvp_no_shipping', 10, 2 );
function bloyal_wvp_no_shipping( $needs_shipping, $product ) {
	try {
		if ( $product->is_virtual() ) {
			$needs_shipping = false;
		} else {
			add_filter( 'woocommerce_cart_needs_shipping', '__return_true' );
		}
		return $needs_shipping;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'init', 'bloyal_stop_heartbeat', 1 );
function bloyal_stop_heartbeat() {
	wp_deregister_script( 'heartbeat' );
}

add_action( 'woocommerce_order_item_add_action_buttons', 'bloyal_resubmit_order_button', 10, 1 );
function bloyal_resubmit_order_button( $order ) {
	try {
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && $order->get_status() != 'auto-draft' && get_option( 'bloyal_use_order_engine' ) == 'true' ) {
			echo '<button type="button" onclick="resubmit_order_to_bloyal(' . esc_attr( $order->get_id() ) . ');" class="button generate-items">' . __( 'Submit Order to bLoyal' ) . '</button>';
			echo '<input type="hidden" value="" id="resubmit" name="renew_order" />';
			echo '<div id="loading_resubmit" class="loading" style="width: 100%;height: 100%;top: 0;left: 0;position: fixed;display: block;opacity: 0.7;background-color: #fff;z-index: 999998;text-align: center;display:none;" hidden> <p><img style="position: absolute;top: 300px;left: 700px;z-index: 999999;" id="loading-image" src="' . esc_url( BLOYAL_URL ) . 'assets/images/puff.svg"/>Please Wait</p> </div>';
		}
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'woocommerce_before_cart', 'bloyal_custom_checkout_message', 10, 1 );
add_action( 'woocommerce_cart_is_empty', 'bloyal_custom_checkout_message', 10, 1 );
function bloyal_custom_checkout_message() {
	$bloyal_unexisted_woo_products = WC()->session->get( 'bloyal_unexisted_woo_products' );
	if ( isset( $bloyal_unexisted_woo_products ) && ! empty( $bloyal_unexisted_woo_products ) ) {
		echo '<div class="woocommerce-error">Following Cart Products are not present in woocommerce database.';
		foreach ( $bloyal_unexisted_woo_products as $product ) {
			$product_name = $product['product_name'];
			echo '<li>';
			echo esc_attr( $product['product_name'] );
			echo '</li>';
		}
		echo '</div>';
		WC()->session->set( 'bloyal_unexisted_woo_products', null );
	}
}

add_action(
	'rest_api_init',
	function ( $server ) {
		$server->register_route(
			'cart',
			'/cart',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => 'bloyal_view_cart_in_woocommerce',
			)
		);
	}
);

function bloyal_fetch_orders_after_modified_date( WP_REST_Request $request ) {
	try {
		$bloyal_obj = new BloyalController();
		return $bloyal_obj->bloyal_fetch_orders_after_modified_date( $request );
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_fetch_customers_after_modified_date( WP_REST_Request $request ) {
	try {
		$bloyal_obj = new BloyalController();
		return $bloyal_obj->bloyal_fetch_customers_after_modified_date( $request );
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_fetch_products_by_sku( WP_REST_Request $request ) {
	try {
		$bloyal_obj = new BloyalController();
		$param      = $request['modified_date'];
		$param1     = $request['sku'];
		if ( strlen( $param ) ) {
			return $bloyal_obj->bloyal_fetch_products_after_modified_date( $request );
		} else {
			return $bloyal_obj->bloyal_fetch_product_by_sku( $request );
		}
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_update_products( WP_REST_Request $request ) {
	try {
		$bloyal_obj = new BloyalController();
		return $bloyal_obj->bloyal_update_products( $request );
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_view_cart_in_woocommerce( WP_REST_Request $request ) {
	try {
		$param = $request['CartId'];
		if ( empty( $param ) ) {
			$param = $request['cart_uid'];
		}
		$bloyal_obj = new BloyalController();
		$bloyal_obj->bloyal_view_cart_in_woocommerce( $param );
		wp_redirect( wc_get_checkout_url() );
		exit;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'woocommerce_order_refunded', 'bloyal_order_refunded', 10, 2 );
function bloyal_order_refunded( $order_id, $refund_id ) {
	try {
		$bloyal_obj = new BloyalController();
		$result     = $bloyal_obj->bloyal_order_refunded( $order_id, $refund_id );
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

/**
	* This function is used to apply custom coupons
	*
	* @param array $coupons_enabled
	*
	* @return array
*/

add_filter( 'woocommerce_coupons_enabled', 'bloyal_apply_custom_coupon_before_coupon_table', 1 );
function bloyal_apply_custom_coupon_before_coupon_table( $coupons_enabled ) {
	try {
		if ( isset( $_POST['coupon_code'] ) ) {
			$coupon_code = sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) );
			$bloyal_obj = new BloyalController();
			$res        = $bloyal_obj->bloyal_validate_coupon( $coupon_code );
		}
		return $coupons_enabled;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

/**
	* This function is used to handle coupon errors
 *
	* @param string $err
	* @param string $error_code
	* @param string $coupon
	*
	* @return coupon message
*/

add_filter( 'woocommerce_coupon_error', 'bloyal_woocommerce_get_coupon_error', 10, 3 );
function bloyal_woocommerce_get_coupon_error( $err, $error_code, $coupon ) {
	try {
		return 105 == $error_code ? false : $err;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'admin_enqueue_scripts', 'bloyal_enqueue_scripts' );
/**
 * Function to enqueue bloyal script.
 *
 * @param null
 *
 * @return void
 */
function bloyal_enqueue_scripts() {
	try {
		wp_enqueue_script( 'bloyal_scripts', BLOYAL_URL . 'assets/js/bloyal.js?version=1.33', array( 'jquery' ), '1.33.0', true );
		wp_register_style( 'bloyal_style_sheet', BLOYAL_URL . 'assets/css/bloyal.css', array(), '1.0' );
		wp_enqueue_style( 'bloyal_style_sheet' );
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

/**
	* Function to include bloyal script(frontend).
 *
	* @param null
	*
	* @return void
*/

add_action( 'wp_enqueue_scripts', 'bloyal_include_scripts' );
function bloyal_include_scripts() {
	$snippets_src     = "https://snippets.bloyal.io/bLoyalSnippetLoader.js?ver=2.0.0";
	wp_enqueue_script( 'bLoyalSnippetLoader',  $snippets_src, array(), '2.0.0', true);
	$plugin_slug = 'woocommerce-multiple-addresses';
	wp_enqueue_script( $plugin_slug . '-plugin-script', plugins_url( 'assets/js/shipping_details.js', __FILE__ ), array( 'jquery' ), '1.0.8', true );
	wp_register_style( 'bloyal_checkout_style_sheet', BLOYAL_URL . '/assets/css/bloyal_checkout.css', array(), '1.0' );
	wp_enqueue_style( 'bloyal_checkout_style_sheet' );
	wp_localize_script(
		$plugin_slug . '-plugin-script',
		'WCMA_Ajax',
		array(
			'ajaxurl'               => admin_url( 'admin-ajax.php' ),
			'id'                    => 0,
			'wc_multiple_addresses' => wp_create_nonce( 'wc-multiple-addresses-ajax-nonce' ),
		)
	);
}

add_action( 'wp_head', 'bloyal_myplugin_ajaxurl' );
function bloyal_myplugin_ajaxurl() {
	echo '<script type="text/javascript">
           var ajaxurl = "' . esc_js( admin_url( 'admin-ajax.php' )) . '";
         </script>';
}

/**
	* This function is used to add meta box to configuartion page
	*
	* @return void
*/

add_action( 'add_meta_boxes', 'bloyal_add_meta_box' );
function bloyal_add_meta_box() {
	try {
		add_meta_box( 'bloyalmeta', 'bLoyal Configuration', 'show_meta', 'bLoyal', 'normal', 'high', null );
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'admin_menu', 'bloyal_add_dashboard_menu' );
function bloyal_add_dashboard_menu() {
	try {
		$access_key_snippets        = get_option( 'bloyal_access_key' );
		add_menu_page( '', 'bLoyal', '', __FILE__, '', plugins_url( 'assets/images/bloyal_logo_16x16.png', __FILE__ ), 24 );
		add_submenu_page( __FILE__, 'configuration', 'Loyalty Configuration', 'manage_options', 'bconfig', 'bloyal_add_config_submenu', 7, plugins_url() . '/bloyal/view/Logo.jpg' );
		if(!empty($access_key_snippets)){	
		  add_submenu_page( __FILE__, 'configuration', 'Order Processing Configuration', 'manage_options', 'bconfig_order_processing', 'bloyal_add_config_submenu_orders', 7, plugins_url() . '/bloyal/view/Logo.jpg' );
		  add_submenu_page( __FILE__, 'bloyal snippets', 'Web Snippets', 'manage_options', 'bloyal_snippets', 'bloyal_snippets_add_snippets_detail_submenu', plugins_url() . '/bloyal/view/Logo.jpg' );
		  add_submenu_page( __FILE__, 'bloyal click and collect', 'Click & Collect', 'manage_options', 'bloyal_click_and_collect', 'bloyal_click_and_collect_config_submenu', plugins_url() . '/bloyal/view/Logo.jpg' );
		}
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_add_config_submenu() {
	try {
		$bloyal_view_obj = new BloyalView();
		$bloyal_view_obj->bloyal_render_config_submenu_page();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_add_config_submenu_orders() {
	try {
		$bloyal_view_obj = new BloyalView();
		$bloyal_view_obj->bloyal_render_config_submenu_page_order_processing();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_click_and_collect_config_submenu() {
	try {
		$bloyal_view_obj = new BloyalView();
		$bloyal_view_obj->bloyal_click_and_collect_config_submenu_page();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'wp_ajax_get_bloyal_access_key_by_apikey', 'bloyal_get_access_key' );
function bloyal_get_access_key() {
	try {
		function get_access_key_by_apikey() {
			try {

				$bloyal_obj                = new BloyalController();
				$domain_name               = sanitize_text_field( wp_unslash( $_POST['post_domain_name'] ) );
				$api_key                   = sanitize_text_field( wp_unslash( $_POST['post_api_key'] ) );
				$domain_url                = sanitize_text_field( wp_unslash( $_POST['post_domain_url'] ) );
				$is_bloyal_custom_url_used = sanitize_text_field( wp_unslash( $_POST['post_custom_api_url_used'] ) );
				$custom_grid_api_url       = sanitize_text_field( wp_unslash( $_POST['post_custom_grid_api_url'] ) );
				return $bloyal_obj->bloyal_get_access_key_curl( $domain_name, $api_key, $domain_url, $is_bloyal_custom_url_used, $custom_grid_api_url );
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
				wp_send_json( json_encode(
					array(
						'status'        => 'error',
						'error_msg_api' => $ex->getMessage(),
						'code'          => 500,
					)
				) );
				die();
			}
		}
		wp_send_json( get_access_key_by_apikey() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
		wp_send_json( json_encode(
			array(
				'status'        => 'error',
				'error_msg_api' => $ex->getMessage(),
				'code'          => 500,
			)
		) );
		die();
	}
}

add_action( 'wp_ajax_resubmit_bloyal_order_data', 'bloyal_order_details_to_resubmit' );
function bloyal_order_details_to_resubmit() {
	try {
		function order_details_to_resubmit() {
			try {
				$resubmit_obj = new BLOYAL_ResubmitOrderController();
				$order_id     = sanitize_text_field( wp_unslash( $_POST['post_order_id'] ) );
				return $resubmit_obj->make_resubmit_request( $order_id );
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
				return $ex->getMessage();
			}
		}
		wp_send_json( order_details_to_resubmit() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
		return $ex->getMessage();
	}
}

add_action( 'wp_ajax_test_bloyal_access_key_by_apikey', 'bloyal_test_access_key' );
function bloyal_test_access_key() {
	try {
		function test_access_key_by_apikey() {
			try {
				$bloyal_obj          = new BloyalController();
				$domain_name         = sanitize_text_field( wp_unslash( $_POST['post_domain_name'] ) );
				$api_key             = sanitize_text_field( wp_unslash( $_POST['post_api_key'] ) );
				$domain_url          = sanitize_text_field( wp_unslash( $_POST['post_domain_url'] ) );
				$access_key          = sanitize_text_field( wp_unslash( $_POST['post_access_key'] ) );
				$custom_api_url_used = sanitize_text_field( wp_unslash( $_POST['post_custom_api_url_used'] ) );
				$custom_grid_api_url = sanitize_text_field( wp_unslash( $_POST['post_custom_grid_api_url'] ) );
				$custom_payment_api_url = sanitize_text_field( wp_unslash( $_POST['post_custompayment_api_url'] ) );
				return $bloyal_obj->bloyal_test_access_key_curl( $domain_name, $api_key, $domain_url, $access_key, $custom_api_url_used, $custom_grid_api_url, $custom_payment_api_url );
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
				wp_send_json( json_encode(
					array(
						'status'        => 'error',
						'error_msg_api' => $ex->getMessage(),
						'code'          => 500,
					)
				) );
				die();
			}
		}
		wp_send_json( test_access_key_by_apikey() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
		echo json_encode(
			array(
				'status'        => 'error',
				'error_msg_api' => $ex->getMessage(),
				'code'          => 500,
			)
		);
		die();
	}
}

add_action( 'wp_ajax_check_access_key_before_save', 'bloyal_check_bloyal_access_key_before_save' );
function bloyal_check_bloyal_access_key_before_save() {
	try {
		function check_bloyal_access_key() {
			try {
				$bloyal_obj             = new BloyalController();
				$domain_name            = sanitize_text_field( wp_unslash( $_POST['post_domain_name'] ) );
				$api_key                = sanitize_text_field( wp_unslash( $_POST['post_api_key'] ) );
				$domain_url             = sanitize_text_field( wp_unslash( $_POST['post_domain_url'] ) );
				$access_key             = sanitize_text_field( wp_unslash( $_POST['post_access_key'] ) );
				$custom_api_url_used    = sanitize_text_field( wp_unslash( $_POST['post_custom_api_url_used'] ) );
				$custom_grid_api_url    = sanitize_text_field( wp_unslash( $_POST['post_custom_grid_api_url'] ) );
				$custom_payment_api_url = sanitize_text_field( wp_unslash( $_POST['post_custompayment_api_url'] ) );
				return $bloyal_obj->bloyal_test_access_key_curl( $domain_name, $api_key, $domain_url, $access_key, $custom_api_url_used, $custom_grid_api_url, $custom_payment_api_url );
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
		wp_send_json( check_bloyal_access_key() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'wp_ajax_save_bloyal_configuration_data', 'bloyal_save_configuration_data' );
function bloyal_save_configuration_data() {
	try {

		function save_configuration_data() {
			try {
				$bloyal_obj              = new BloyalController();
				return $bloyal_obj->bloyal_save_configuration_data_wpdb();
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
		echo save_configuration_data();
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'wp_ajax_save_bloyal_configuration_data_order_processing', 'bloyal_save_configuration_data_order_processing' );
function bloyal_save_configuration_data_order_processing() {
	try {
		function save_configuration_data() {
			try {
				$bloyal_obj              = new BloyalController();
				return $bloyal_obj->bloyal_save_configuration_data_wpdb_order_processing();
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
		wp_send_json( save_configuration_data() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'wp_ajax_save_bloyal_accesskeyverification_data', 'bloyal_save_accesskeyverification_data' );
function bloyal_save_accesskeyverification_data() {
	try {
		function save_accesskeyverification_data() {
			try {
				$bloyal_obj              = new BloyalController();
				$access_key_verification = sanitize_text_field( wp_unslash( $_POST['post_access_key_verification'] ) );
				$is_custom_bloyal_url    = sanitize_text_field( wp_unslash( $_POST['post_bloyal_custom_url'] ) );
				return $bloyal_obj->bloyal_save_accesskeyverification_data_wpdb( $access_key_verification, $is_custom_bloyal_url );
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
		wp_send_json( save_accesskeyverification_data() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'wp_ajax_get_bloyal_configuration_details', 'bloyal_get_configuration_details' );
function bloyal_get_configuration_details() {
	try {
		function get_configuration_details() {
			try {
				$bloyal_obj = new BloyalController();
				return $bloyal_obj->bloyal_get_configuration_details_from_wpdb();
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
		wp_send_json( get_configuration_details() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'wp_ajax_get_bloyal_accesskeyverification_details', 'bloyal_get_accesskeyverification_details' );
function bloyal_get_accesskeyverification_details() {
	try {
		function get_accesskeyverification_details() {
			try {
				$bloyal_obj = new BloyalController();
				return $bloyal_obj->bloyal_get_accesskeyverification_details_from_wpdb();
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
		wp_send_json( get_accesskeyverification_details() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'woocommerce_proceed_to_checkout', 'bloyal_cart_coupon_remove' );
function bloyal_cart_coupon_remove() {
	$loyalty_block_setting = get_option( 'loyalty_block' );
	if ( empty( $loyalty_block_setting ) || false == $loyalty_block_setting ) {
		bLoyalLoggerService::write_custom_log( "\r\n ========== loyalty_block ============\r\n" . $loyalty_block_setting );
	}
	?>
	<a href= "javascript:;" onclick="loyalty_toggle()" id="bloyaldiscountsummary">[View Loyalty And Discounts]</a><br>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			var loyalty_block_setting = '<?php echo esc_js($loyalty_block_setting); ?>';
			if (loyalty_block_setting == 'true'){
				jQuery(".pop").css("visibility", "visible");
				jQuery(".pop").css("display", "block");
			}
			else{
				jQuery(".pop").css("visibility", "hidden");
				jQuery(".pop").css("display", "none");
			}
		});
		function loyalty_toggle(){
			if (jQuery("#pop").css('visibility') === 'hidden') {
				jQuery(".pop").css("visibility", "visible");
				jQuery(".pop").css("display", "block");
			}
			else {
				jQuery(".pop").css("visibility", "hidden");
				jQuery(".pop").css("display", "none");
			}
		}
	</script>
	<?php
		$time_pre           = microtime( true );
		$bloyal_obj         = new BloyalController();
		$loyalty_block_html = $bloyal_obj->bloyal_fetch_discount_summary();
		$loyalty_block_html = json_decode( $loyalty_block_html );
		$loyalty_block_html = $loyalty_block_html->msg;
		echo '<div class="pop" id="pop">' . wp_kses_post( $loyalty_block_html ) . '</div>';
		$session_bloyal_coupons = WC()->session->get( 'bloyal_coupon' );
	if ( isset( $session_bloyal_coupons ) ) {
		if ( $session_bloyal_coupons ) {
			echo '<span class="remove_coupons" style="cursor: pointer;">[Remove Coupons]</span>';
		}
	}
		echo '<br>';
	$time_post = microtime( true );
	$exec_time = $time_post - $time_pre;
}

add_filter( 'woocommerce_payment_gateways', 'bloyal_custom_payment_gateways' );
function bloyal_custom_payment_gateways( $gateways ) {
	$access_key        = get_option('bloyal_access_key');
    if(!empty($access_key)){
		$gateways[] = 'Bloyal_Gift_Card_Gateway';
		$gateways[] = 'Bloyal_Loyalty_Dollar_Gateway';
		$gateways[] = 'Bloyal_Stored_Payment_Gateway';
	}
	return $gateways;
}

add_action( 'wp_ajax_check_loyalty_dollar_balance', 'bloyal_check_loyalty_dollar_balance' ); // Loyalty dollar balance check
add_action( 'wp_ajax_nopriv_check_loyalty_dollar_balance', 'bloyal_check_loyalty_dollar_balance' ); // Loyalty dollar balance check
function bloyal_check_loyalty_dollar_balance() {
	$balance          = ( new LoyaltyDollarPaymentController() )->getLoyaltyDollarBalance();
	$message          = '';
	$applyFullBalance = get_option( 'bloyal_apply_full_balance_loyalty' );
	$applyIncrementOf = get_option( 'bloyal_apply_in_increment_of_loyalty' );
	//for guest user(Loyalty dollar) 
	if ( ! is_user_logged_in() ) {
		$message ="Unable to check balance for guest user...";
	}
	else{
		if ( is_int( $balance ) ) {
			$message = 'Unable to check your balance.';
		} else {
			$balance    = $balance->GetCardBalanceResult->AvailableBalance;
			$cart_total = WC()->cart->total;
			if ( $applyFullBalance == 'false' ) {
				if ( ( $cart_total <= $balance ) && ( $balance != 0 ) ) {
					$message = 'Current balance is : $' . $balance . ', choose an amount to redeem.';
				}
				if ( ( $cart_total > $balance ) && ( $balance != 0 ) ) {
					$message = 'Current balance is : $' . $balance . ', choose an amount to redeem.';
				}
				if ( $balance == 0 ) {
					$message = 'Current balance is $' . $balance . ', place order with another tender.';
				}
			} else {
				if ( ( $cart_total <= $balance ) && ( $balance != 0 ) ) {
					$message = 'Current balance is : $' . $balance . ' ,  $' . $cart_total . ' will be redeemed on order completion.';
					WC()->session->set( 'loyalty_balance_check', floatval( $balance ) );
				}
				if ( ( $cart_total > $balance ) && ( $balance != 0 ) ) {
					$message = "Click 'Apply Balance' to pay $" . $balance . ', then place order with another tender.';
					WC()->session->set( 'loyalty_balance_check', floatval( $balance ) );
				}

				if ( $balance == 0 ) {
					$message = 'Current balance is $' . $balance . ', place order with another tender.';
				}
			}
			$cartTotal = $cart_total;
		}
	}
	wp_send_json( json_encode(
		array(
			'status'            => 'success',
			'message'           => $message,
			'balance'           => $balance,
			'apply_fullbalance' => $applyFullBalance,
			'apply_incrementof' => $applyIncrementOf,
			'cart_total'        => $cart_total,
		)
	) );
	die();
}

add_action( 'wp_ajax_apply_loyalty_dollar_balance', 'bloyal_apply_loyalty_dollar_balance' );
add_action( 'wp_ajax_nopriv_apply_loyalty_dollar_balance', 'bloyal_apply_loyalty_dollar_balance' );
function bloyal_apply_loyalty_dollar_balance() {
	$loyaltyBalance   = ( new LoyaltyDollarPaymentController() )->getLoyaltyDollarBalance();
	$message          = '';
	$applyFullBalance = get_option( 'bloyal_apply_full_balance_loyalty' );
	if ( is_int( $balance ) ) {
		$message = 'Unable to check balance, use another method or try again later.';
	} else {
		$cart_total = WC()->cart->total;
		if ( $applyFullBalance == 'false' ) {
			$balance          = sanitize_text_field( wp_unslash( $_POST['post_redeem_amount'] ) );
			$availableBalance = $loyaltyBalance->GetCardBalanceResult->AvailableBalance;
			if ( $cart_total <= $balance ) {
				$message = 'Current balance is : $' . $availableBalance . ' ,  $' . $balance . ' will be redeemed on order completion.';
				WC()->session->set( 'partial_loyalty_amount_redeem', floatval( $balance ) );
			} else {
				$message = 'Current balance is : $' . $availableBalance . ' , Applied Balance is  $' . $balance . ', then place order with another tender.';
				WC()->session->set( 'partial_loyalty_amount', floatval( $balance ) );
			}
		} else {
			$balance = $loyaltyBalance->GetCardBalanceResult->AvailableBalance;
			if ( $cart_total <= $balance ) {
				$message = 'Current balance is : $' . $balance . ' ,  $' . $cart_total . ' will be redeemed on order completion.';
			} else {
				WC()->session->set( 'partial_loyalty_amount', floatval( $balance ) );
			}
		}
	}
	$response      = array(
		'status'  => 'success',
		'message' => $message,
		'balance' => $balance,
	);
	$response_json = json_encode( $response );
	wp_send_json( $response_json );
	die();
}

add_action( 'wp_ajax_check_gift_card_balance', 'bloyal_check_gift_card_balance' ); // Gift card balance check
add_action( 'wp_ajax_nopriv_check_gift_card_balance', 'bloyal_check_gift_card_balance' ); // Gift card balance check
function bloyal_check_gift_card_balance() {
	$gift_card_number = sanitize_text_field( wp_unslash( $_POST['gift_card_number'] ) );
	$balance          = ( new PaymentController() )->getGiftcardBalance( $gift_card_number );
	$message          = '';
	$applyFullBalance = get_option( 'bloyal_apply_full_balance_giftcard' );
	$applyIncrementOf = get_option( 'bloyal_apply_in_increment_of_giftcard' );
	if ( is_int( $balance ) ) {
		$message = 'Invalid gift card number.';
	} else {
		$balance    = $balance->GetCardBalanceResult->AvailableBalance;
		$cart_total = WC()->cart->total;
		if ( $applyFullBalance == 'false' ) {
			if ( ( $cart_total <= $balance ) && ( $balance != 0 ) ) {
				$message = 'Current balance is : $' . $balance . ' ,  choose an amount to redeem.';
			}
			if ( ( $cart_total > $balance ) && ( $balance != 0 ) ) {
				$message = 'Current balance is $' . $balance . ', choose an amount to redeem.';
			}
			if ( $balance == 0 ) {
				$message = 'Current balance is $' . $balance . ', place order with another tender.';
			}
			WC()->session->set( 'partial_gift_number_increment_of', $gift_card_number );
		} else {
			if ( ( $cart_total <= $balance ) && ( $balance != 0 ) ) {
				$message = 'Current balance is : $' . $balance . ' ,  $' . $cart_total . ' will be redeemed on order completion.';
			}
			if ( ( $cart_total > $balance ) && ( $balance != 0 ) ) {
				$message = "Click 'Apply Balance' to pay $" . $balance . ', then place order with another tender.';
			}
			if ( $balance == 0 ) {
				$message = 'Current balance is $' . $balance . ', place order with another tender.';
			}
		}
	}
	$response      = array(
		'status'            => 'success',
		'message'           => $message,
		'balance'           => $balance,
		'apply_fullbalance' => $applyFullBalance,
		'apply_incrementof' => $applyIncrementOf,
		'cart_total'        => $cart_total,
	);
	$response_json = json_encode( $response );
	wp_send_json( $response_json );
	die();
}

add_action( 'wp_ajax_apply_gift_card_balance', 'bloyal_apply_gift_card_balance' );
add_action( 'wp_ajax_nopriv_apply_gift_card_balance', 'bloyal_apply_gift_card_balance' );
function bloyal_apply_gift_card_balance() {
	$gift_card_number = sanitize_text_field( wp_unslash( $_POST['gift_card_number'] ) );
	$giftBalance      = ( new PaymentController() )->getGiftcardBalance( $gift_card_number );
	$message          = '';
	$applyFullBalance = get_option( 'bloyal_apply_full_balance_giftcard' );
	if ( is_int( $giftBalance ) ) {
		$message = 'Invalid gift card number.';
	} else {
		$cart_total = WC()->cart->total;
		if ( $applyFullBalance === 'false' ) {
			$redeemBalance = sanitize_text_field( wp_unslash( $_POST['post_redeem_amount'] ) );
			$balance       = $giftBalance->GetCardBalanceResult->AvailableBalance;
			if ( $cart_total <= $redeemBalance ) {
				$message = 'Current balance is : $' . $balance . ' ,  $' . $redeemBalance . ' will be redeemed on order completion.';
				WC()->session->set( 'partial_gift_amount_redeem', floatval( $balance ) );
			} else {
				$message = 'Current balance is : $' . $balance . ' ,  Applied Balance is  $' . $redeemBalance . ', then place order with another tender.';
				$balance = $redeemBalance;
				WC()->session->set( 'partial_gift_amount', floatval( $redeemBalance ) );
				WC()->session->set( 'partial_gift_number', $gift_card_number );
			}
		} else {
			$balance = $giftBalance->GetCardBalanceResult->AvailableBalance;
			if ( $cart_total <= $balance ) {
				$message = 'Current balance is : $' . $balance . ' ,  $' . $cart_total . ' will be redeemed on order completion.';
			} elseif ( $balance == 0 ) {
				$message = 'Current balance is : $' . $balance . ' , then place order with another tender';
			} else {
				WC()->session->set( 'partial_gift_amount', floatval( $balance ) );
				WC()->session->set( 'partial_gift_number', $gift_card_number );
			}
		}
	}
	$response      = array(
		'status'  => 'success',
		'message' => $message,
		'balance' => $balance,
	);
	$response_json = json_encode( $response );
	wp_send_json( $response_json );
	wp_die();
}

add_action( 'woocommerce_review_order_after_order_total', 'bloyal_add_remove_link_after_total' );
function bloyal_add_remove_link_after_total() {
	$bloyal_session_coupons = WC()->session->get( 'bloyal_coupon' );
	if ( isset( $bloyal_session_coupons ) ) {
		if ( $bloyal_session_coupons ) {
			?>
			<tr>
				<td colspan="2">
					<?php echo '<span class="remove_coupons" style="cursor: pointer;">[Remove Coupons]</span>'; ?>
				</td>
			</tr>
			<?php
		}
	}
}

add_action( 'woocommerce_after_cart_item_quantity_update', 'bloyal_after_cart_item_quantity_update', 10, 3 );
function bloyal_after_cart_item_quantity_update( $cart_item_key, $quantity, $old_quantity ) {
	try {
		$bloyal_obj = new BloyalController();
		$bloyal_obj->bloyal_after_cart_item_quantity_update();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_action( 'wp_ajax_refresh_cached_data', 'bloyal_refresh_cached_data' );
function bloyal_refresh_cached_data() {
	try {
		$bloyal_obj = new BloyalController();
		$action     = 'Connectors/ContextInfo';
		$isFailed   = false;
		bLoyalLoggerService::write_custom_log( "Get Devices Request URL \r\n" . $action . "\r\n ======================\r\n", 1 );
		$result = $bloyal_obj->send_curl_request( '', $action, 'grid', 0 );
		bLoyalLoggerService::write_custom_log( "Get Devices Response \r\n" . json_encode( $result ) . "\r\n ======================\r\n", 1 );
		update_option( 'Device_Code', $result->data->DeviceCode );
		$device_code = $result->data->DeviceCode;
		$action      = $device_code . '/SnippetProfiles';
		bLoyalLoggerService::write_custom_log( "Get Snippets Request URL \r\n" . $action . "\r\n ======================\r\n", 1 );
		$snippet_code = array(
			'all'           => array(),
			'informational' => array(),
			'confirmation'  => array(),
			'problem'       => array(),
		);
		$result       = $bloyal_obj->send_curl_request( '', $action, 'web_snippets_api_url', 0 );
		bLoyalLoggerService::write_custom_log( "Get Snippets Response \r\n" . json_encode( $result ) . "\r\n ======================\r\n", 1 );
		$payment_snippet_code = '';
		if ( 'success' == $result->status ) {
			if ( ! empty( $result->data ) ) {
				foreach ( $result->data as $key => $snippet ) {
					if ( 'Alert' == $snippet->SnippetType ) {
						$SnippetSettings = json_decode( $snippet->SnippetSettings );
						$snippet_code[ strtolower( $SnippetSettings->AlertType ) ][] = $snippet->Code;
					}
					if ( 'PaymentMethod' == $snippet->SnippetType ) {
						$payment_snippet_code = $snippet->Code;
						update_option( 'payment_snippets_codes', $payment_snippet_code );
					}
				}
			}
			$snippet_code['informational'] = array_merge( $snippet_code['informational'], $snippet_code['all'] );
			$snippet_code['confirmation']  = array_merge( $snippet_code['confirmation'], $snippet_code['all'] );
			$snippet_code['problem']       = array_merge( $snippet_code['problem'], $snippet_code['all'] );
			update_option( 'bloyal_snippets_codes', json_encode( $snippet_code ) );
		} else {
			$errorMessage = 'Could not refresh at the moment, please try again later';
			$isFailed     = true;
		}
		$result_tenders = $bloyal_obj->send_curl_request( '', 'Tenders', 'grid', 0 );
		if ( 'success' == $result_tenders->status ) {
			update_option( 'bloyal_Tenders', json_encode( $result_tenders ) );
		} else {
			$errorMessage = 'Could not refresh at the moment, please try again later';
			$isFailed     = true;
		}
		$result_ShippingCarriers = $bloyal_obj->send_curl_request( '', 'ShippingCarriers', 'grid', 0 );
		if ( 'success' == $result_ShippingCarriers->status ) {
			update_option( 'bloyal_ShippingCarriers', json_encode( $result_ShippingCarriers ) );
		} else {
			$errorMessage = 'Could not refresh at the moment, please try again later';
			$isFailed     = true;
		}
		wp_send_json( json_encode(
			array(
				'status'       => $isFailed ? 'failed' : 'success',
				'errorMessage' => $errorMessage,
			)
		) );
		die;
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
		echo json_encode(
			array(
				'status'       => 'failed',
				'errorMessage' => $ex->getMessage(),
			)
		);
		die;
	}
}

add_action( 'woocommerce_admin_order_data_after_billing_address', 'bloyal_custom_date_of_birth_field_billing', 10, 1 );
function bloyal_custom_date_of_birth_field_billing( $order ) {
	echo '<p><strong>' .esc_html( __( 'Date of Birth' )) . ':</strong> <br/>' . esc_attr( get_post_meta( $order->get_id(), '_bloyal_billing_date_of_birth', true ) ). '</p>';
}



add_action( 'woocommerce_admin_order_data_after_shipping_address', 'bloyal_custom_date_of_birth_field_shipping', 10, 1 );
function bloyal_custom_date_of_birth_field_shipping( $order ) {
	$method = $order->get_shipping_methods();
	echo '<p><strong>' .esc_html( __( 'Gift message' ) ). ':</strong> <br/>' . esc_attr( get_post_meta( $order->get_id() , '_bloyal_shipping_gift_message', true )) . '</p>';
	echo '<p><strong>' .esc_html( __( 'Email address' ) ). ':</strong> <br/>' . esc_attr( get_post_meta( $order->get_id() , '_bloyal_shipping_email', true )) . '</p>';
	echo '<p><strong>' .esc_html( __( 'Phone' ) ). ':</strong> <br/>' . esc_attr( get_post_meta( $order->get_id() , '_bloyal_shipping_phone', true ) ). '</p>';
	echo '<p><strong>' . esc_html(__( 'Date of Birth' )) . ':</strong> <br/>' . esc_attr( get_post_meta( $order->get_id() , '_bloyal_shipping_date_of_birth', true ) ). '</p>';
}

add_filter( 'woocommerce_checkout_fields', 'bloyal_custom_shipping_checkout_fields' );
function bloyal_custom_shipping_checkout_fields( $fields ) {
	global $current_user;
	$otherAddrs                       = array();
	$objectMultipleShippingController = new BLOYAL_MultipleShippingAddressController();
	$current_user                     = wp_get_current_user();
	if ( ! empty( $current_user ) ) {
		$loggedin_customer_id    = isset( $current_user->ID ) && ( $current_user->ID ) ? $current_user->ID : '';
		$loggedin_customer_email = isset( $current_user->user_email ) && ( $current_user->user_email ) ? $current_user->user_email : '';
	}
	$addrs_count            = 0;
	$isDisplayPhone         = get_option( 'bloyal_display_Phone' );
	$isRequiredPhone        = get_option( 'bloyal_required_Phone' );
	$isDisplayEmail         = get_option( 'bloyal_display_Email' );
	$isRequiredEmail        = get_option( 'bloyal_required_Email' );
	$isDisplayOrderComments = get_option( 'bloyal_display_order_comments' );
	$isDisplayAddressBook   = get_option( 'bloyal_display_address_Book' );
	if ( 'true' == $isDisplayAddressBook ) {
		$cart_data                = WC()->session->get( 'bloyal_cart_data');
		
		$customer_uid             = $cart_data->Cart->Customer->Uid;
		$customer_code            = $cart_data->Cart->Customer->Code;
		// code to get calculatecart customer information (Start)
		$customer_Fname           = $cart_data->Cart->Customer->FirstName;
		$customer_Lname           = $cart_data->Cart->Customer->LastName;
		$customer_Comp_name       = $cart_data->Cart->Customer->CompanyName;
		$customer_Address1        = $cart_data->Cart->Customer->Address->Address1;
		$customer_Address2        = $cart_data->Cart->Customer->Address->Address2;
		$customer_City            = $cart_data->Cart->Customer->Address->City;
		$customer_State           = $cart_data->Cart->Customer->Address->State;
		$customer_PostalCode      = $cart_data->Cart->Customer->Address->PostalCode;
		$customer_Country         = $cart_data->Cart->Customer->Address->Country;
		$customer_StateCode       = $cart_data->Cart->Customer->Address->StateCode;
		$customer_StateName       = $cart_data->Cart->Customer->Address->StateName;
		$customer_CountryCode     = $cart_data->Cart->Customer->Address->CountryCode;
		$customer_CountryName     = $cart_data->Cart->Customer->Address->CountryName;
		$customer_MobilePhone     = $cart_data->Cart->Customer->MobilePhone;
		$customer_EmailAddress    = $cart_data->Cart->Customer->EmailAddress;
		$customer_BirthDate       = $cart_data->Cart->Customer->BirthDate;
		if ( null != $customer_BirthDate && '' != $customer_BirthDate ) {
			$dateTime = new DateTime($customer_BirthDate);
			$formatted_cust_bill_birth_date = $dateTime->format('Y-m-d');
		}
		// code to get calculatecart customer information (end)
		
		$saved_addrs             = $objectMultipleShippingController->get_multiple_shipping_addresses( $customer_uid, $customer_code );
		$saved_addresses         = json_encode( $saved_addrs );
		$saved_addresses         = json_decode( $saved_addresses, true );
		if($saved_addresses['status'] == 'success'){
            $total_saved_addrs = count($saved_addresses['data']);
		}
		$addresses    = array();
		$addresses[0] = __( 'Please Configure shipping address..', 'woocommerce' );
		if ( ! empty( $saved_addrs ) ) {
			$addresses[0] = __( 'Select an address...', 'woocommerce' );
			for ( $addrs_count = 0; $addrs_count < $total_saved_addrs; $addrs_count++ ) {
				$addresses[ $addrs_count + 1 ] = $saved_addresses['data'][ $addrs_count ]['Title'];
			}
		}
		$alt_field          = array(
			'label'    => __( 'Saved addresses', 'woocommerce' ),
			'required' => false,
			'class'    => array( 'form-row' ),
			'clear'    => true,
			'type'     => 'select',
			'priority' => 1,
			'options'  => $addresses,
		);
		$alt_save_address   = array(
			'label'    => __( 'Save this Address', 'woocommerce' ),
			'id'       => 'save_address_checkbox',
			'name'     => 'save_address_checkbox',
			'type'     => 'checkbox',
			'priority' => 2,
			'class'    => array( 'form-row' ),
		);
		$fields['shipping'] = bloyal_array_unshift_assoc( $fields['shipping'], 'alt_save_address', $alt_save_address );
		$fields['shipping'] = bloyal_array_unshift_assoc( $fields['shipping'], 'shipping_alt', $alt_field );
	}
	if ( 'true' == $isDisplayPhone ) {
		$fields['shipping']['shipping_phone'] = array(
			'type'     => 'text',
			'label'    => __( 'Phone', 'woocommerce' ),
			'clear'    => false,
			'validate' => array( 'phone' ),
			'required' => 'true' == $isRequiredPhone ? true : false,
			'priority' => 110,
		);
	}
	if ( 'true' == $isDisplayEmail ) {
		$fields['shipping']['shipping_email'] = array(
			'type'     => 'email',
			'label'    => __( 'Email address', 'woocommerce' ),
			'clear'    => false,
			'validate' => array( 'email' ),
			'required' => 'true' == $isRequiredEmail ? true : false,
			'priority' => 120,
		);
	}
	if ( 'true' == $isDisplayOrderComments ) {
		$fields['billing']['order_comments2'] = array(
			'type'     => 'text',
			'label'    => __( 'Order Instructions', 'woocommerce' ),
			'clear'    => false,
			'required' => false,
			'priority' => 130,
		);
		add_filter( 'woocommerce_enable_order_notes_field', '__return_false' );
	}
	// code to set bLoyal calculatecart customer information on checkout page (start)
	$fields['billing']['billing_first_name']['default']=$customer_Fname;
	$fields['billing']['billing_last_name']['default']=$customer_Lname;
    $fields['billing']['billing_address_1']['default']=$customer_Address1;
    $fields['billing']['billing_address_2']['default']=$customer_Address2;
    $fields['billing']['billing_city']['default']=$customer_City;
    $fields['billing']['billing_state']['default']=$customer_StateName;
    $fields['billing']['billing_email']['default']=$customer_EmailAddress;
    $fields['billing']['billing_phone']['default']=$customer_MobilePhone;
    $fields['billing']['billing_postcode']['default']=$customer_PostalCode;
	$fields['billing']['billing_company']['default']=$customer_Comp_name;
	$fields['billing']['billing_country']['default']=$customer_CountryName;
	$fields['billing']['billing_birth_date']['default'] = $formatted_cust_bill_birth_date;
	// code to set bLoyal calculatecart customer information on checkout page (end)
	return $fields;
}

function bloyal_array_unshift_assoc( &$arr, $key, $val ) {
	$arr         = array_reverse( $arr, true );
	$arr[ $key ] = $val;
	return array_reverse( $arr, true );
}

add_action( 'wp_ajax_shipping_address_change', 'bloyal_ajax_checkout_change_shipping_address' );
add_action( 'wp_ajax_nopriv_shipping_address_change', 'bloyal_ajax_checkout_change_shipping_address' );
function bloyal_ajax_checkout_change_shipping_address() {
	global $current_user;
	$objectMultipleShippingController = new BLOYAL_MultipleShippingAddressController();
	$current_user                     = wp_get_current_user();
	if ( ! empty( $current_user ) ) {
		$loggedin_customer_id    = isset( $current_user->ID ) && ( $current_user->ID ) ? $current_user->ID : '';
		$loggedin_customer_email = isset( $current_user->user_email ) && ( $current_user->user_email ) ? $current_user->user_email : '';
		$cart_data                = WC()->session->get( 'bloyal_cart_data');
		$customer_uid             = $cart_data->Cart->Customer->Uid;
		$customer_code            = $cart_data->Cart->Customer->Code;
		$saved_addrs             = $objectMultipleShippingController->get_multiple_shipping_addresses( $customer_uid, $customer_code );
		$saved_addresses         = json_encode( $saved_addrs );
		$saved_addresses         = json_decode( $saved_addresses, true );		
	}
	$selected =  sanitize_text_field( wp_unslash( $_REQUEST['id'] ) );
	$selected = $selected - 1;
	header( 'Content-Type: application/json' );
	$saved_birth_date = null;
	$saved_birth_date = $saved_addresses['data'][ $selected ]['BirthDate'];
	if ( null != $saved_birth_date && '' != $saved_birth_date ) {
		$saved_birth_date        = explode( '-', $saved_birth_date );
		$saved_date              = explode( 'T', $saved_birth_date[2] );
		$formatted_shipping_date = $saved_birth_date[1] . '/' . $saved_date[0] . '/' . $saved_birth_date[0];
	}
	$current_shipping_addrs = array(
		'shipping_first_name'   => $saved_addresses['data'][ $selected ]['FirstName'],
		'shipping_last_name'    => $saved_addresses['data'][ $selected ]['LastName'],
		'shipping_company'      => $saved_addresses['data'][ $selected ]['Company'],
		'shipping_country'      => $saved_addresses['data'][ $selected ]['Address']['CountryName'],
		'shipping_country_code' => $saved_addresses['data'][ $selected ]['Address']['CountryCode'],
		'shipping_address_1'    => $saved_addresses['data'][ $selected ]['Address']['Address1'],
		'shipping_address_2'    => $saved_addresses['data'][ $selected ]['Address']['Address2'],
		'shipping_city'         => $saved_addresses['data'][ $selected ]['Address']['City'],
		'shipping_state'        => $saved_addresses['data'][ $selected ]['Address']['StateName'],
		'shipping_state_code'   => $saved_addresses['data'][ $selected ]['Address']['StateCode'],
		'shipping_postcode'     => $saved_addresses['data'][ $selected ]['Address']['PostalCode'],
		'shipping_birth_date'   => $formatted_shipping_date,
		'shipping_phone'        => $saved_addresses['data'][ $selected ]['Phone1'],
		'shipping_email'        => $saved_addresses['data'][ $selected ]['EmailAddress'],
		'uid'                   => $saved_addresses['data'][ $selected ]['Uid'],
	);
	WC()->session->set( 'current_shipping_address_uid', $saved_addresses['data'][ $selected ]['Uid'] );
	$current_shipping_addrs = wp_send_json(  $current_shipping_addrs );
	exit;
}

add_action( 'woocommerce_before_order_notes', 'bloyal_gift_order_field' );
function bloyal_gift_order_field( $checkout ) {
	echo '<div class="gift_order_class">';
	woocommerce_form_field(
		'gift_order_checkbox',
		array(
			'type'     => 'checkbox',
			'label'    => __( 'Gift Order' ),
			'id'       => 'gift_order_id',
			'name'     => 'gift_order_id',
			'required' => false,
		),
		$checkout->get_value( 'gift_order_checkbox' )
	);
	echo '</div>';
	echo '<div class="gift_message_box_class">';
	woocommerce_form_field(
		'gift_message_box',
		array(
			'type'        => 'textarea',
			'placeholder' => __( 'Gift Message' ),
			'id'          => 'gift_message_box_area',
			'name'        => 'gift_message_box_area',
			'required'    => true,
		),
		$checkout->get_value( 'gift_message_box' )
	);
	echo '</div>';
}

add_action( 'wp_ajax_save_shipping_address', 'bloyal_ajax_save_shipping_address' );
function bloyal_ajax_save_shipping_address() {
	$save_shipping_address = sanitize_text_field( wp_unslash( $_POST['save_shipping_addrs'] ) );
	bLoyalLoggerService::write_custom_log( "\r\n ========== saved value ============\r\n" . $save_shipping_address );
	WC()->session->set( 'check_save_shipping_address', $save_shipping_address );
}

function bloyal_add_payment_methods_to_checkout() {
	try {
		$objectAddPaymentMethods = new AddPaymentMethods();
		$current_user            = wp_get_current_user();
		if ( ! empty( $current_user ) ) {
			$payment_methods = $objectAddPaymentMethods->save_payment_methods( $current_user->ID, $current_user->user_email );
		}
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

function bloyal_custom_cron_schedule( $schedules ) {
	$schedules['every_one_day'] = array(
		'interval' => 86400,
		'display'  => esc_html__( 'Every One day' ),
	);
	return $schedules;
}

add_filter( 'cron_schedules', 'bloyal_custom_cron_schedule' );
if ( ! wp_next_scheduled( 'bloyal_cron_hook' ) ) {
	wp_schedule_event( time(), 'every_one_day', 'bloyal_cron_hook' );
}

//add_action( 'bloyal_cron_hook', 'bloyal_cron_function' );
function bloyal_cron_function() {
	$bloyal_obj                = new BloyalController();
	$domain_name               = get_option( 'bloyal_domain_name' );
	$domain_url                = get_option( 'bloyal_domain_url' );
	$is_bloyal_custom_url_used = get_option( 'is_bloyal_custom_api_url' );
	$bloyal_urls               = $bloyal_obj->bloyal_get_service_urls( $domain_name, $domain_url, $is_bloyal_custom_url_used );
	bLoyalLoggerService::write_custom_log( "\r\n ========== Get Service URLs updated by CRON Job ============\r\n" );
	if ( empty( $domain_name ) || false == $domain_name ) {
		bLoyalLoggerService::write_custom_log( "\r\n ========== bloyal_domain_name ============\r\n" . $domain_name );
	}
}

add_filter( 'woocommerce_order_shipping_to_display', 'bloyal_filter_woocommerce_order_shipping_to_display', 10, 2 );
function bloyal_filter_woocommerce_order_shipping_to_display( $shipping, $instance ) {
	if ( $instance->get_shipping_method() == 'Local pickup $0.00' ) {
		return $shipping;
	}
	if ( $instance->get_shipping_method() == 'Free shipping' ) {
		return $shipping;
	}
	if ( $instance->get_total_shipping() == 0.00 ) {
		$shipping = '$0 ' . $instance->get_shipping_method();
		return $shipping;
	}
	return $shipping;
};

add_filter( 'woocommerce_package_rates', 'bloyal_sort_shipping_services_by_priority', 10, 2 );
function bloyal_sort_shipping_services_by_priority( $rates, $package ) {
	// OPTIMIZE and sort by priority
	$bloyal_rates     = WC()->session->get( 'custom_bloyal_shipping_rates' );
	$bloyal_rate_uids = array();
	foreach ( $bloyal_rates as $rate ) {
		array_push( $bloyal_rate_uids, $rate['id'] );
	}
	if ( ! $rates ) {
		return;
	}
	$rate_priorities    = array();
	$woocommerce_native = array();
	foreach ( $rates as $rate ) {
		$bloyal_id     = $rate->id;
		$rate_priority = array_search( $bloyal_id, $bloyal_rate_uids );
		if ( $rate_priority != false ) {
			array_push( $rate_priorities, $rate_priority );
		} else {
			if ( $rate_priority === 0 ) {
				array_push( $rate_priorities, 0 );
			} else {
				array_push( $rate_priorities, 1000 );
			}
		}
	}
	array_multisort( $rate_priorities, SORT_NUMERIC, SORT_ASC, $rates );
	return $rates;
}

function bloyal_log_cron_schedule( $schedules ) {
	$schedules['every_ten_seconds'] = array(
		'interval' => 10,
		'display'  => esc_html__( 'Every Ten Seconds' ),
	);
	return $schedules;
}

add_filter( 'cron_schedules', 'bloyal_log_cron_schedule' );
if ( ! wp_next_scheduled( 'bloyal_cron_log_hook' ) ) {
	wp_schedule_event( time(), 'every_ten_seconds', 'bloyal_cron_log_hook' );
}

add_action( 'bloyal_cron_log_hook', 'bloyal_upload_bloyal_log' );
function bloyal_upload_bloyal_log() {
	try {
		$bLoyal_logger_service_obj = new bLoyalLoggerService();
		$bLoyal_logger_service_obj->uploadLog();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

// End of WooCommerce Cart Ops -----------------------------------------------------------------------------------------------

// Start bLoyal Snippets -----------------------------------------------------------------------------------------------------

function bloyal_snippets_add_snippets_detail_submenu() {
	$bloyal_snippets_view_obj = new BloyalSnippetsView();
	$bloyal_snippets_view_obj->bloyal_snippets_render_snippets_detail_submenu_page();
}

add_action( 'admin_enqueue_scripts', 'bloyal_snippets_enqueue_scripts' );
add_action( 'wp_enqueue_script', 'bloyal_snippets_enqueue_scripts' );
/**
 * Initialize bloyal_snippets_enqueue_scripts.
 *
 * @return void
 */
function bloyal_snippets_enqueue_scripts() {
	wp_enqueue_script( 'bloyal_snippets_scripts', BLOYAL_URL . '/assets/js/bloyal_snippets.js?version=1.24', array( 'jquery' ), '1.24.0', true );
	wp_register_style( 'bloyal_snippets_style_sheet', BLOYAL_URL . '/assets/css/bloyal_snippets.css', array(), '1.0' );
	wp_enqueue_style( 'bloyal_snippets_style_sheet' );
}

add_action( 'wp_ajax_get_access_key_by_apikey_snippets', 'bloyal_snippets_get_access_key' );
function bloyal_snippets_get_access_key() {
	try {
		$master_settings_obj                 = new BloyalSnippetsSettingsController();
		$obj                                 = new stdClass();
		$obj->domain_name_snippets           = sanitize_text_field( wp_unslash( $_POST['post_domain_name_snippets'] ) );
		$obj->api_key_snippets               = sanitize_text_field( wp_unslash( $_POST['post_api_key_snippets'] ) );
		$obj->domain_url_snippets            = sanitize_text_field( wp_unslash(  $_POST['post_domain_url_snippets'] ) );
		$obj->is_custom_web_snippet_url_used = sanitize_text_field( wp_unslash( $_POST['post_is_custom_web_snippet_url_used'] ) );
		$obj->snippets_custom_gridapi_url    = sanitize_text_field( wp_unslash( $_POST['post_snippetscustomgridapiurl'] ) );
		wp_send_json( $master_settings_obj->bloyal_snippets_get_access_key_curl( $obj ) );
		die();

	} catch ( Exception $ex ) {
		wp_send_json( json_encode(
			array(
				'status'        => 'error',
				'error_msg_api' => $ex->getMessage(),
				'code'          => 500,
			)
		) );
		die();
	}
}

add_action( 'wp_ajax_test_access_key_by_apikey_snippets', 'bloyal_snippets_test_access_key' );
function bloyal_snippets_test_access_key() {
	try {
		$master_settings_obj                 = new BloyalSnippetsSettingsController();
		$obj                                 = new stdClass();
		$obj->domain_name_snippets           = sanitize_text_field( wp_unslash( $_POST['post_domain_name_snippets'] ) );
		$obj->api_key_snippets               = sanitize_text_field( wp_unslash( $_POST['post_api_key_snippets'] ) );
		$obj->domain_url_snippets            = sanitize_text_field( wp_unslash( $_POST['post_domain_url_snippets'] ) );
		$obj->access_key_snippets            = sanitize_text_field( wp_unslash( $_POST['post_access_key_snippets'] ) );
		$obj->is_custom_web_snippet_url_used = sanitize_text_field( wp_unslash( $_POST['post_is_custom_web_snippet_url_used'] ) );
		$obj->snippets_custom_gridapi_url    = sanitize_text_field( wp_unslash( $_POST['post_snippetscustomgridapiurl'] ) );
		wp_send_json( $master_settings_obj->bloyal_snippets_test_access_key( $obj ) );
		die();
	} catch ( Exception $ex ) {
		wp_send_json( json_encode(
			array(
				'status'        => 'error',
				'error_msg_api' => $ex->getMessage(),
				'code'          => 500,
			)
		) );
		die();
	}
}

add_action( 'wp_ajax_check_snippets_access_key_before_save', 'bloyal_check_bloyal_snippets_access_key_before_save' );
function bloyal_check_bloyal_snippets_access_key_before_save() {
	function check_bloyal_snippets_access_key() {
		$master_settings_obj                 = new BloyalSnippetsSettingsController();
		$obj                                 = new stdClass();
		$obj->domain_name_snippets           = sanitize_text_field( wp_unslash( $_POST['post_domain_name_snippets'] ) );
		$obj->api_key_snippets               = sanitize_text_field( wp_unslash( $_POST['post_api_key_snippets'] ) );
		$obj->domain_url_snippets            = sanitize_text_field( wp_unslash( $_POST['post_domain_url_snippets'] ) );
		$obj->access_key_snippets            = sanitize_text_field( wp_unslash( $_POST['post_access_key_snippets'] ) );
		$obj->is_custom_web_snippet_url_used = sanitize_text_field( wp_unslash( $_POST['post_is_custom_web_snippet_url_used'] ) );
		$obj->snippets_custom_gridapi_url    = sanitize_text_field( wp_unslash( $_POST['post_snippetscustomgridapiurl'] ) );
		return $master_settings_obj->bloyal_snippets_test_access_key( $obj );
	}
	wp_send_json( check_bloyal_snippets_access_key() );
	wp_deregister_script( 'autosave' );
	die();
}

add_action( 'wp_ajax_save_configuration_data', 'bloyal_snippets_save_configuration_data' );
function bloyal_snippets_save_configuration_data() {
	$master_settings_obj                              = new BloyalSnippetsSettingsController();
	$oblBloyalSnippetController                       = new BloyalSnippetsController();
	$obj_save_conf                                    = new stdClass();
	$obj_save_conf->domain_name_snippets              = sanitize_text_field( wp_unslash( $_POST['post_domain_name_snippets'] ) );
	$obj_save_conf->api_key_snippets                  = sanitize_text_field( wp_unslash( $_POST['post_api_key_snippets'] ) );
	$obj_save_conf->domain_url_snippets               = sanitize_text_field( wp_unslash( $_POST['post_domain_url_snippets'] ) );
	$obj_save_conf->access_key_snippets               = sanitize_text_field( wp_unslash( $_POST['post_access_key_snippets'] ) );
	$obj_save_conf->radio_web_snippet                 = sanitize_text_field( wp_unslash( $_POST['post_radio_web_snippet_url'] ) );
	$obj_save_conf->custom_web_snippet_url            = sanitize_text_field( wp_unslash( $_POST['post_custom_web_snippet_url'] ) );
	$obj_save_conf->default_device                    = sanitize_text_field( wp_unslash( $_POST['post_default_device'] ) );
	$obj_save_conf->post_use_wordpress_login          = sanitize_text_field( wp_unslash( $_POST['post_use_wordpress_login'] ) );
	$obj_save_conf->post_use_bloyal_login             = sanitize_text_field( wp_unslash( $_POST['post_use_bloyal_login'] ) );
	$obj_save_conf->snippetscustomgridapiurl          = sanitize_text_field( wp_unslash( $_POST['post_snippetscustomgridapiurl'] ) );
	$obj_save_conf->snippetscustomloyaltyengineapiurl = sanitize_text_field( wp_unslash( $_POST['post_snippetscustomloyaltyengineapiurl'] ) );
	$obj_save_conf->snippetcustomwebsnippetapiurl     = sanitize_text_field( wp_unslash( $_POST['post_snippetcustomwebsnippetapiurl'] ) );
	$obj_save_conf->snippetcustomwebsnippethtmlapiurl = sanitize_text_field( wp_unslash( $_POST['post_snippetcustomwebsnippethtmlapiurl'] ) );
		$obj_save_conf->page_id                           = sanitize_text_field( wp_unslash( $_POST['post_page'] ) );
	bLoyalSnipetsLoggerService::write_custom_log( "Save bLoyal Snippets Configuration data \n\r" . json_encode( $obj_save_conf ) . "\r\n ======================\r\n", 1 );
	wp_send_json( $master_settings_obj->bloyal_snippets_save_configuration_data_wpdb( $obj_save_conf ) );
	die();
}

add_action( 'wp_ajax_save_snippets_accesskeyverification_data', 'bloyal_snippets_save_accesskeyverification_data' );
function bloyal_snippets_save_accesskeyverification_data() {
	$master_settings_obj              = new BloyalSnippetsSettingsController();
	$access_key_verification_snippets = sanitize_text_field( wp_unslash( $_POST['post_access_key_verification_snippets'] ) );
	$custom_web_snippet_url_used      = sanitize_text_field( wp_unslash( $_POST['post_custom_web_snippet_url_used'] ) );
	wp_send_json( $master_settings_obj->bloyal_snippets_save_accesskeyverification_data_wpdb( $access_key_verification_snippets, $custom_web_snippet_url_used ) );
	die();
}

add_action( 'wp_ajax_get_configuration_details', 'bloyal_snippets_get_configuration_details' );
function bloyal_snippets_get_configuration_details() {
	$master_settings_obj = new BloyalSnippetsSettingsController();
	wp_send_json( $master_settings_obj->bloyal_snippets_get_configuration_details_from_wpdb() );
	die();
}

add_action( 'wp_ajax_get_accesskeyverification_details', 'bloyal_snippets_get_accesskeyverification_details' );
function bloyal_snippets_get_accesskeyverification_details() {
	$master_settings_obj = new BloyalSnippetsSettingsController();
	wp_send_json( $master_settings_obj->bloyal_snippets_get_accesskeyverification_details_from_wpdb() );
	die();
}

add_action( 'wp_ajax_fetch_snippets_associated_with_device', 'bloyal_fetch_snippets_wrt_device' );
function bloyal_fetch_snippets_wrt_device() {
	$bloyal_snippets_obj = new BloyalSnippetsController();
	$device_context      = $bloyal_snippets_obj->send_curl_request( '', 'contextdevices', 'loyaltyengine', 0 );
	$selected_device     = $device_context->data->Code;
	wp_send_json( $bloyal_snippets_obj->bloyal_fetch_snippets_wrt_device( $selected_device ) );
	die();
}

add_action( 'wp_loaded', 'bloyal_add_shortcodes_for_all_devices' );
function bloyal_add_shortcodes_for_all_devices() {
	$arrSelectedSnippetShortCodes = get_option( 'bloyal_saved_device_snippets_lists' );
	if ( ! empty( $arrSelectedSnippetShortCodes ) ) {
		bloyal_addSnippetData( $arrSelectedSnippetShortCodes );
	} else {
		$bloyal_snippets_obj = new BloyalSnippetsController();
		$device_context      = $bloyal_snippets_obj->send_curl_request( '', 'contextdevices', 'loyaltyengine', 0 );
		$savedSnippetDevice  = $device_context->data->Code;
		$snippetsData        = $bloyal_snippets_obj->bloyal_fetch_snippets_wrt_device( $savedSnippetDevice );
		$snippetsData        = json_decode( $snippetsData, true );
		if ( ! empty( $snippetsData ) ) {
			$arrSavedDeviceSnippetsList = array();
			foreach ( $snippetsData['snippets_list'] as $key => $snippet ) {
				$arrSavedDeviceSnippetsList[] = array(
					'snippet_shortcode' => str_replace( array( '[', ']' ), '', $snippet['snippet_shortcode'] ),
					'snippet_type'      => $snippet['snippet_type'],
				);
			}
			update_option( 'bloyal_saved_device_snippets_lists', $arrSavedDeviceSnippetsList );
			bloyal_addSnippetData( $arrSavedDeviceSnippetsList );

		}
	}
}

function bloyal_addSnippetData( $arrSelectedSnippetShortCodes ) {
	global $wp_session;
	if ( ! empty( $arrSelectedSnippetShortCodes ) ) {
		foreach ( $arrSelectedSnippetShortCodes as $key => $strShortCode ) {
			add_shortcode( $strShortCode['snippet_shortcode'], 'bloyal_shortcode_callback' );
			$wp_session[ $strShortCode['snippet_shortcode'] ] = $strShortCode['snippet_type'];
		}
	}
}

function bloyal_shortcode_callback( $atts, $content, $tag ) {
	ob_start();
	global $wp_session;
	$shortcode_name_content = explode( '_', $tag );
	$snipppet_code          = $shortcode_name_content[1];
	$device_code            = $shortcode_name_content[2];
	$club_code              = isset( $shortcode_name_content[3] ) ? $shortcode_name_content[3] : '';
	$snippet_type           = '';
	if ( isset( $wp_session[ $tag ] ) ) {
		$snippet_type = $wp_session[ $tag ];
	}
	$bloyal_snippets_obj = new BloyalSnippetsController();
	echo $bloyal_snippets_obj->bloyal_fetch_snippet_html( $atts, $snipppet_code, $device_code, $snippet_type, $club_code );
	return ob_get_clean();
}

add_action( 'wp_ajax_save_snippets_uss_css_status', 'bloyal_save_snippets_uss_css_status' );
function bloyal_save_snippets_uss_css_status() {
	$bloyal_snippets_obj = new BloyalSnippetsController();
	$checked_snippets    = sanitize_text_field( wp_unslash( $_POST['post_checked_snippets'] ) );
	$unchecked_snippets  = sanitize_text_field( wp_unslash( $_POST['post_unchecked_snippets'] ) );
	wp_send_json( $bloyal_snippets_obj->bloyal_save_snippets_uss_css_status( $checked_snippets, $unchecked_snippets) );
	die();
}	

add_filter( 'woocommerce_login_redirect', 'bloyal_redirect_woocommerce_loggedin_user_to_snippet' );
function bloyal_redirect_woocommerce_loggedin_user_to_snippet() {
	if ( isset( $_REQUEST['ReturnUrl'] ) ) {
		$return_url = sanitize_url( wp_unslash( $_REQUEST['ReturnUrl'] ) );
		wp_redirect( $return_url );
		exit;
	} else {
		wp_redirect( home_url() );
		exit;
	}
}

add_filter( 'woocommerce_registration_redirect', 'bloyal_redirect_woocommerce_registered_user_to_snippet' );
function bloyal_redirect_woocommerce_registered_user_to_snippet() {
	if ( isset( $_REQUEST['ReturnUrl'] ) ) {
		$return_url = sanitize_url( wp_unslash( $_REQUEST['ReturnUrl'] ) );
		wp_redirect( $return_url );
		exit;
	} else {
		$_wp_http_referer = sanitize_url( wp_unslash( $_REQUEST['_wp_http_referer'] ) );
		wp_redirect( $_wp_http_referer );
		wp_redirect( home_url() );
		exit;
	}
}

add_filter( 'wp_login', 'bloyal_redirect_wordpress_loggedin_user_to_snippet' );
function bloyal_redirect_wordpress_loggedin_user_to_snippet() {
	$page_id = get_option( 'page_id' );
	if ( 0 == $page_id ) {
		if ( isset( $_REQUEST['ReturnUrl'] ) ) {
			$return_url = sanitize_url( wp_unslash( $_REQUEST['ReturnUrl'] ) );
			wp_redirect( $return_url );
			exit;
		} else {
			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$_wp_http_referer = sanitize_url( wp_unslash( $_REQUEST['_wp_http_referer'] ) );
				wp_redirect( $_wp_http_referer );
				exit;
			}
		}
	} else {
		if ( isset( $_REQUEST['ReturnUrl'] ) ) {
			$return_url = sanitize_url( wp_unslash( $_REQUEST['ReturnUrl'] ) );
			if ( strpos( $return_url, '?ReturnUrl=' ) ) {
				$redirect_url = explode( 'ReturnUrl=', $return_url );
				wp_redirect( $redirect_url[1] );
				exit;
			}
			wp_redirect( $return_url );
			exit;
		} else {
			if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
				$_wp_http_referer = sanitize_url( wp_unslash( $_REQUEST['_wp_http_referer'] ) );
				wp_redirect( $_wp_http_referer );
				exit;
			}
		}
	}
}

add_filter( 'user_register', 'bloyal_redirect_wordpress_registered_user_to_snippet' );
function bloyal_redirect_wordpress_registered_user_to_snippet( $user_id ) {
	if ( empty( $_REQUEST['ReturnUrl'] ) ) {
		$current_user = wp_get_current_user();
		wp_set_current_user( $current_user->data->ID );
		wp_set_auth_cookie( $current_user->data->ID, false, is_ssl() );
	} else {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, false, is_ssl() );
		if ( ! empty( $_REQUEST['ReturnUrl'] ) ) {
			$return_url = sanitize_url( wp_unslash( $_REQUEST['ReturnUrl'] ) );
			wp_redirect( $return_url );
		}
	}
}

add_action( 'wp_ajax_refresh_saved_data', 'bloyal_snippets_refresh_data' );
function bloyal_snippets_refresh_data() {
	update_option( 'bloyal_saved_device_snippets_lists', '' );
	update_option( 'bLoyal_snippets_devices', '' );
	update_option( 'bLoyal_web_snippets_storeUid', '' );
	wp_send_json( json_encode(
		array(
			'status' => 'success',
		)
	) );
	die;
}

add_action( 'wp_ajax_refresh_device_codes', 'bloyal_refresh_device_codes' );
function bloyal_refresh_device_codes() {
	$bloyal_snippets_obj = new BloyalSnippetsController();
	$bloyal_snippets_obj->bloyal_fetch_all_devices();
	wp_send_json( json_encode(
		array(
			'status' => 'success',
		)
	) );
	die;
}

add_filter( 'cron_schedules', 'bloyal_snippet_custom_cron_schedule' );
function bloyal_snippet_custom_cron_schedule( $schedules ) {
	$schedules['every_twenty_four_hours'] = array(
		'interval' => 86400,
		'display'  => __( 'Every 24 hours' ),
	);
	return $schedules;
}

if ( ! wp_next_scheduled( 'bloyal_snippet_cron_hook' ) ) {
	wp_schedule_event( time(), 'every_twenty_four_hours', 'bloyal_snippet_cron_hook' );
}

add_action( 'bloyal_snippet_cron_hook', 'bloyal_snippet_cron_function' );
function bloyal_snippet_cron_function() {
	$bloyal_snippets_obj                 = new BloyalSnippetsController();
	$obj                                 = new stdClass();
	$obj->domain_name_snippets           = get_option( 'bloyal_domain_name' );
	$obj->api_key_snippets               = get_option( 'bloyal_api_key' );
	$obj->domain_url_snippets            = get_option( 'bloyal_domain_url' );
	$obj->is_custom_web_snippet_url_used = get_option( 'bloyal_snippets_radio_web_snippet_api' );
	$bloyal_urls                         = $bloyal_snippets_obj->bloyal_snippets_get_service_urls( $obj );
	bLoyalSnipetsLoggerService::write_custom_log( "\r\n ========== Get Service URLs updated by CRON Job ============\r\n" );
}


// REACT functionality ------------------------------------------------------------------------------------------------------

add_action( 'wp_loaded', 'bloyal_add_react_shortcodes' );
function bloyal_add_react_shortcodes() {
	$snippet_list     = get_option( 'bloyal_saved_device_snippets_lists' );
	$react_shortcodes = array();
	foreach ( $snippet_list as $snippet ) {
		$snippet_components = explode( '_', $snippet['snippet_shortcode'] );
		$react_shortcode1   = $snippet['snippet_shortcode'] . '_react';
		$react_shortcode2   = $snippet['snippet_shortcode'] . '_React';
		$react_shortcode3   = $snippet['snippet_shortcode'] . '_REACT';
		add_shortcode( $react_shortcode1, 'bloyal_react_club_shortcode_callback' );
		add_shortcode( $react_shortcode2, 'bloyal_react_club_shortcode_callback' );
		add_shortcode( $react_shortcode3, 'bloyal_react_club_shortcode_callback' );
		array_push( $react_shortcodes, $react_shortcode );
	}
	//update_option( 'bloyal_saved_snippets_react', $react_shortcodes );
}

// add_action('init', 'register_session');
function bloyal_register_session() {
	if ( ! session_id() ) {
		session_start();
	}
}

add_filter( 'query_vars', 'bloyal_add_session_to_url' );
function bloyal_add_session_to_url( $aVars ) {
	$aVars[] = 'bL_sk';
	return $aVars;
}

add_filter( 'rewrite_rules_array', 'bloyal_add_rewrite_rules' );
function bloyal_add_rewrite_rules( $aRules ) {
	$aNewRules = array( 'msds-pif/([^/]+)/?$' => 'index.php?pagename=msds-pif&msds_pif_cat=$matches[1]' );
	$aRules    = $aNewRules + $aRules;
	return $aRules;
}



function bloyal_react_club_shortcode_callback( $atts, $content, $tag ) {
	ob_start();
	global $wp_session;
	$shortcode_name_content = explode( '_', $tag );
	$snipppet_code          = $shortcode_name_content[1];
	$device_code            = $shortcode_name_content[2];
	$club_code              = isset( $shortcode_name_content[3] ) ? $shortcode_name_content[3] : '';
	$snippet_type           = '';
	if ( isset( $wp_session[ $tag ] ) ) {
		$snippet_type = $wp_session[ $tag ];
	}
	$bloyal_snippets_obj = new BloyalSnippetsController();
	echo esc_attr($bloyal_snippets_obj->bloyal_fetch_snippet_html( $atts, $snipppet_code, $device_code, $snippet_type, $club_code ));
	return ob_get_clean();
}


add_action( 'wp_ajax_bloyal_activate_provisioning_snippets_url', 'bloyal_activate_provisioning_snippets_url' );
function bloyal_activate_provisioning_snippets_url() {

	$provisioningStatus = WC()->session->get( 'provisioning_status' );
	bLoyalLoggerService::write_custom_log( "activate_provisioning_snippets_url" . $$provisioningStatus );
	if ( $provisioningStatus == 'Pending' ) {
		$provisioningResponse = WC()->session->get( 'provisioning_response' );
		wp_send_json( $provisioningResponse );
	} else {

		try {
			//this api use for create bLoyal service provisioning session after the plugin activated in bLoyal loyalty configuration page click on activate device by bLoyal
			$bloyal_provisioning_api_url = 'https://domain.bloyal.io/api/v4/ServiceProvisioning/Sessions/Devices';
			$stor_name                   = get_option( 'blogname' );
			$stor_code                   = get_option( 'woocommerce_version' );
			$apiRequestData              = array(
				'ConnectorKey'   => '94EC0683-BCA6-42EF-B172-86D11D4B1E56',
				'StoreCode'      => str_replace( ' ', '', $stor_name ) . str_replace( '.', '', $stor_code ),
				'StoreName'      => $stor_name,
				'DeviceCode'     => str_replace( ' ', '', $stor_name ) . str_replace( '.', '', $stor_code ),
				'DeviceName'     => 'wp_' . str_replace( ' ', '', $stor_name ),
				'CustomSettings' => array(),
				'Uid'            => '',
				'Status'         => 'Pending',
				'StatusMessage'  => '',
				'RetryCount'     => 0,
			);
			$args = array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $apiRequestData ),
				'method'  => 'POST',
				'timeout' => 45,
			);
			bLoyalLoggerService::write_custom_log( "provisioning_api apiRequestData" . $args );
			$response         = wp_remote_post( $bloyal_provisioning_api_url, $args );
            bLoyalLoggerService::write_custom_log( "provisioning_api apiRequest" . $response );
			$response_status  = wp_remote_retrieve_response_code( $response );
			bLoyalLoggerService::write_custom_log( "provisioning_api apiResponse" . $response_status );
			$response         = wp_remote_retrieve_body( $response );
			if ( ! empty( json_decode( $response, true ) ) ) {
				$results               = json_decode( $response, true );
				$data_status           = $results['data']['Status'];
				$provision_session_key = $results['data']['ProvisionSessionKey'];
				WC()->session->set( 'provisioning_status', $data_status );
				WC()->session->set( 'provisioning_key', $provision_session_key );
				WC()->session->set( 'provisioning_response', $response );
			}
			wp_send_json( $response );
		} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
		}
	}
	wp_die();
}

add_action( 'wp_ajax_bloyal_activate_provisioning_device', 'bloyal_activate_provisioning_device' );
function bloyal_activate_provisioning_device() {
	if ( ! empty( WC()->session->get( 'provisioning_key' ) ) ) {
		$provision_session_key              = WC()->session->get( 'provisioning_key' );
		//this api use for get bLoyal service provisioning Sessions after the device success fully provisioned by bLoyal
		$provisioning_session_key_api_url = 'https://domain.bloyal.io/api/v4/ServiceProvisioning/Sessions/' . $provision_session_key;
		$args = array(
			'headers' => array(
				'Content-Type' => 'application/json',
			),
			'method'  => 'GET',
			'timeout' => 45,
		);
		$response        = wp_remote_get( $provisioning_session_key_api_url, $args );
		$response_status = wp_remote_retrieve_response_code( $response );
		$response        = wp_remote_retrieve_body( $response );
		$results = json_decode( $response, true );
		if ( $results['status'] == 'success' ) {
			$data_status = $results['data']['Status'];
			$data_msg    = $results['data']['StatusMessage'];
			if ( $data_status == 'Succeeded' ) {
				WC()->session->__unset( 'provisioning_status' );
				WC()->session->__unset( 'provisioning_response' );
				WC()->session->__unset( 'provisioning_key' );
			} elseif ( $data_status == 'Failed' ) {
				WC()->session->__unset( 'provisioning_status' );
				WC()->session->__unset( 'provisioning_response' );
				WC()->session->__unset( 'provisioning_key' );
			}
			if ( $results['data']['AccessKey'] != '' ) {
				bloyal_update_bLoyal_provisioning_activated_data_wpdb( $results['data'] );
			} else {
				wp_send_json( json_encode(
					array(
						'save_success' => false,
						'Status'       => $data_status,
						'Message'      => 'Device not activated, ' . $data_msg,
					)
				) );
			}
		}
	} else {
		wp_send_json( json_encode(
			array(
				'save_success' => false,
				'Status'       => 'Pending',
			)
		) );
	}
	wp_die();
}

function bloyal_update_bLoyal_provisioning_activated_data_wpdb( $results ) {
	try {

		$access_key_verification = sanitize_text_field( wp_unslash( $_POST['post_access_key_verification'] ) );
		$is_custom_bloyal_url    = sanitize_url( wp_unslash( $_POST['post_bloyal_custom_url'] ) );
		update_option( 'bloyal_access_key_verification', $access_key_verification );
		update_option( 'is_bloyal_custom_api_url', $is_custom_bloyal_url );
		update_option( 'bloyal_domain_name', isset( $results['LoginDomain'] ) ? $results['LoginDomain'] : '' );
		update_option( 'bloyal_api_key', isset( $results['ApiKey'] ) ? $results['ApiKey'] : '' );
		update_option( 'bloyal_domain_url', isset( $results['ServiceUrls']['DirectorUrl'] ) ? $results['ServiceUrls']['DirectorUrl'] : '' );
		update_option( 'bloyal_access_key', isset( $results['AccessKey'] ) ? $results['AccessKey'] : '' );
		// update_option('bloyal_on_account_tender_code', isset($results['post_on_account_tender']) ? $results['post_on_account_tender']: '');
		update_option( 'bloyal_custom_grid_api_url', isset( $results['ServiceUrls']['GridApiUrl'] ) ? $results['ServiceUrls']['GridApiUrl'] : '' );
		update_option( 'bloyal_custom_loyaltyengine_api_url', isset( $results['ServiceUrls']['LoyaltyEngineApiUrl'] ) ? $results['ServiceUrls']['LoyaltyEngineApiUrl'] : '' );
		update_option( 'bloyal_custom_orderengine_api_url', isset( $results['ServiceUrls']['OrderEngineApiUrl'] ) ? $results['ServiceUrls']['OrderEngineApiUrl'] : '' );
		update_option( 'bloyal_custompayment_api_url', isset( $results['ServiceUrls']['PaymentApiUrl'] ) ? $results['ServiceUrls']['PaymentApiUrl'] : '' );
		update_option( 'bloyal_custom_logging_api_url', isset( $results['ServiceUrls']['LoggingApiUrl'] ) ? $results['ServiceUrls']['LoggingApiUrl'] : '' );
		update_option( 'is_bloyal_custom_api_url', isset( $results['ServiceUrls']['DirectorApiUrl'] ) ? $results['ServiceUrls']['DirectorApiUrl'] : '' );
		update_option( 'bloyal_click_and_collect_status', '2' );
		update_option( 'bloyal_click_collect_label', 'Pickup at Store' );
		update_option( 'click_collect_error', 'No store available for active cart.' );

		wp_send_json( json_encode( $results ) );
	} catch ( Exception $exception ) {
		bLoyalLoggerService::write_custom_log( $exception->getMessage(), 3 );
		return $exception->getMessage();
	}
	wp_die();
}

add_action( 'wp_ajax_bloyal_save_bloyal_click_collect_configuration_data', 'bloyal_save_bloyal_click_collect_configuration_data' );
function bloyal_save_bloyal_click_collect_configuration_data() {
	try {

		function save_click_collect_configuration_data() {
			try {
				$bloyal_obj              = new BloyalController();
				return $bloyal_obj->bloyal_save_click_collect_configuration_data_wpdb( );
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
		wp_send_json( save_click_collect_configuration_data() );
		wp_deregister_script( 'autosave' );
		die();
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}


if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	function bloyal_shipping_store_pickup_method() {
		try {
			if ( ! class_exists( 'bloyal_Shipping_Store_Pickup_Method' ) ) {
				class bloyal_Shipping_Store_Pickup_Method extends WC_Shipping_Method {
					/**
					 * Constructor for your shipping class
					 *
					 * @access public
					 * @return void
					 */
					public function __construct( $instance_id = 0 ) {
						$this->id                 = 'bloyal_pickup_store';
						$this->instance_id        = absint( $instance_id );
						$this->method_title       = __( 'bloyal Shipping Pickup at Store' );
						$this->method_description = __( 'Custom Shipping Pickup at Store Method for bloyal' );

						$this->supports = array(
							'shipping-zones',
							'instance-settings',
						);

						$this->instance_form_fields = array(
							'enabled' => array(
								'title'   => __( 'Enable/Disable' ),
								'type'    => 'checkbox',
								'label'   => __( 'Enable this shipping method' ),
								'default' => 'no',
							),
							'title'   => array(
								'title'       => __( 'bloyal Shipping Pickup at Store' ),
								'type'        => 'text',
								'description' => __( 'This controls the title which the user sees during checkout.' ),
								'default'     => __( 'Pickup at Store' ),
								'desc_tip'    => true,
							),
						);
						$this->enabled              = $this->get_option( 'enabled' );
						$this->title                = $this->get_option( 'title' );
						add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
					}
					/**
					 * calculate_shipping function.
					 *
					 * @param array $package (default: array())
					 */
					public function calculate_shipping( $package = array() ) {
						$this->add_rate(
							array(
								'id'    => $this->id . ':' . $this->instance_id,
								'label' => $this->title,
								'cost'  => 0,
							)
						);
					}
				}
			}
		} catch ( Exception $ex ) {
			bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
		}
	}
	add_action( 'woocommerce_shipping_init', 'bloyal_shipping_store_pickup_method' );
	function bloyal_add_bloyal_shipping_store_pickup_method( $methods ) {
		$methods['bloyal_pickup_store'] = 'bloyal_Shipping_Store_Pickup_Method';
		return $methods;

	}
	add_filter( 'woocommerce_shipping_methods', 'bloyal_add_bloyal_shipping_store_pickup_method' );
}


add_filter('script_loader_tag', 'bloyal_add_attributes_to_script', 10, 3);
/**
 * Initialize add_attributes_to_script.
 *
 * @param  mixed $tag string.
 * @param  mixed $handle string.
 * @param  mixed $src string.
 * @return string
 */
function bloyal_add_attributes_to_script( $tag, $handle, $src ) {
	if ( 'bLoyalSnippetLoader' === $handle ) {
		$snippet_domain = get_option( 'bloyal_domain_name' );
		$tag            = '<script type = "text/javascript" data-bloyal-login-domain = "' . $snippet_domain . '" src = "' . esc_url( $src ) . '"></script>';
	}
	return $tag;
}

add_action( 'wp_ajax_bloyal_remove_bloyal_accesskey', 'bloyal_remove_bloyal_access_key' );
function bloyal_remove_bloyal_access_key() {
	try {
		
		$adv_access_key = sanitize_text_field( wp_unslash( $_POST['post_adv_access_key'] ) );
		update_option( 'bloyal_access_key', $adv_access_key);
		update_option( 'bloyal_access_key', $adv_access_key);
		$result = array("message" => "Access Key succefully removed!", "status" => "success");
		wp_send_json( wp_json_encode($result) );
		wp_die();
		
	} catch ( Exception $ex ) {
		bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
	}
}

//store payment method developed by Chetu Developer Saurabh Gupta
add_action( 'wp_ajax_bloyal_stored_payment_method', 'bloyal_stored_payment_method' );
function bloyal_stored_payment_method() {
    if (isset ( $_POST['method_uid'] ) ) {
		$stored_payment_uid  = sanitize_text_field( wp_unslash( $_POST['method_uid'] ) );
		WC()->session->set( 'stored_payment_uid', $stored_payment_uid);
	}
	wp_die();
}


//Pickup at Store locations carrier // Date :- 07/12/2022 Developed by Chetu Developer Saurabh Gupta
function bloyal_store_pickup_locations_data() {
	if( is_cart() || ( is_checkout() ) ) {
		$stores_data = array();
		$click_and_collect_label  = get_option( 'bloyal_click_collect_label' );
		$bloyal_click_and_collect_status = get_option( 'bloyal_click_and_collect_status' );
		if ( empty( $bloyal_click_and_collect_status ) || 1 == $bloyal_click_and_collect_status ) {
			$bloyal_obj              = new BloyalController();
			$bLoyalcartUid           = $bloyal_obj->get_uid();
			$loyalty_engine_api_url  = get_option( 'loyalty_engine_api_url' );
			$access_key              = get_option( 'bloyal_access_key' );
			$action                  = 'carts/pickuplocations?cartUid=' . $bLoyalcartUid;
			//this API use for get the all bLoyal store pickup locations in cart and checkout page bLoyal pickup store shipping method by bLoyal.
			$pickup_location_api_url = $loyalty_engine_api_url . '/api/v4/' . $access_key . '/' . $action;
			$args = array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'method'  => 'GET',
				'timeout' => 45,
			);
			$response             = wp_remote_get( $pickup_location_api_url, $args );
			$response_status      = wp_remote_retrieve_response_code( $response );
			$response             = wp_remote_retrieve_body( $response );
			$pickuplocations_data = json_decode( $response, true );
			$location_error_data  = array();
			if ( isset( $pickuplocations_data['status'] ) && ( $pickuplocations_data['status'] == 'success' ) && ( ! empty( $pickuplocations_data['data'] ) ) ) {
				foreach ( $pickuplocations_data['data'] as $key => $value ) {
					$stores_address = $value['Name'] . ' ' . $value['Address']['Address1'] . ' ' . $value['Address']['City'] . ' ' . $value['Address']['StateName'] . ' ' . $value['Address']['PostalCode'] . ' ' . $value['Address']['CountryName'];
					update_option($value['Code'], $stores_address);
					$stores_data[$value['Code']]   = $value['Name'];
				}
			}
			bLoyalLoggerService::write_custom_log( "\r\n ========== pickuplocations_results ============\r\n" . $response );
		}
	}
	return $stores_data;
}


// Custom function that handle your settings
function bloyal_carrier_settings() {
		$bloyal_shipping_method = '';
		if( is_cart() || ( is_checkout() ) ) {
			$chosen_shipping_methods  = WC()->session->get( 'chosen_shipping_methods' );
			$shipping_method          = sanitize_key( $chosen_shipping_methods[0] );
			if ( strpos( $shipping_method, 'bloyal_pickup_store' ) !== false ) {
				$click_and_collect_label  = get_option( 'bloyal_click_collect_label' );
				$store_pickup_options = bloyal_store_pickup_locations_data();
				$bloyal_shipping_method =  $chosen_shipping_methods[0];
			}
		}
		return array(
			'targeted_methods' => array($bloyal_shipping_method), // Your targeted shipping method(s) in this array
			'field_id'         => 'carrier_name', // Field Id
			'field_type'       => 'select', // Field type
			'field_label'      => $click_and_collect_label, // Leave empty value if the first option has a text (see below).
			'label'            => __("Carrier company","woocommerce"), // for validation and as meta key for orders
			'field_options'    => $store_pickup_options,
		);
}
// Display the custom checkout field
add_action( 'woocommerce_after_shipping_rate', 'bloyal_carrier_company_custom_select_field', 20, 2 );
function bloyal_carrier_company_custom_select_field( $method, $index ) {
	//bloyal_store_pickup_locations();
    extract( bloyal_carrier_settings() ); // Load settings and convert them in variables

    $chosen  = WC()->session->get('chosen_shipping_methods'); // The chosen methods
    $value   = WC()->session->get($field_id);
    $value   = WC()->session->__isset($field_id) ? $value : WC()->checkout->get_value('_'.$field_id);
    $options = array(); // Initializing

    if( ! empty($chosen) && $method->id === $chosen[$index] && in_array($method->id, $targeted_methods)  ) {
        echo '<div class="custom-carrier">';
        //Loop through field otions to add the correct keys
        foreach( $field_options as $key => $option_value ) {
            $option_key = $key == "0" ? '' : $key;
            $options[$option_key] = $option_value;
        }
		
        woocommerce_form_field( $field_id, array(
            'type'     => $field_type,
            'label'    => $field_label, // Not required if the first option has a text.
            'class'    => array('form-row-wide ' . $field_id . '-' . $field_type ),
            'required' => true,
            'options'  => $options,
        ), $value );

        echo '</div>';
    }
}
// jQuery code (client side) - Ajax sender 
add_action( 'wp_footer', 'bloyal_carrier_company_script_js' );
function bloyal_carrier_company_script_js() {
    // Only cart & checkout pages
    if( is_cart() || ( is_checkout() && ! is_wc_endpoint_url() ) ):
    // Load settings and convert them in variables
    extract( bloyal_carrier_settings() );
    $js_variable = is_cart() ? 'wc_cart_params' : 'wc_checkout_params';
    // jQuery Ajax code
    ?>
    <script type="text/javascript">
    jQuery( function($){
        if (typeof <?php echo esc_js( $js_variable ); ?> === 'undefined')
            return false;

        $(document.body).on( 'change', 'select#<?php echo esc_js( $field_id ); ?>', function(){
            var value = $(this).val();
            $.ajax({
                type: 'POST',
                url: <?php echo esc_js($js_variable); ?>.ajax_url,
                data: {
                    'action': 'carrier_name',
                    'value': value
                },
                success: function (result) {
                    // console.log(result); // Only for testing (to be removed)
                }
            });
        });
    });
    </script>
    <?php
    endif;
}

// The Wordpress Ajax PHP receiver
add_action( 'wp_ajax_carrier_name', 'bloyal_set_carrier_company_name' );
add_action( 'wp_ajax_nopriv_carrier_name', 'bloyal_set_carrier_company_name' );
function bloyal_set_carrier_company_name() {
    if ( isset($_POST['value']) ){
        // Load settings and convert them in variables
        extract( bloyal_carrier_settings() );

        if( empty($_POST['value']) ) {
            $value = 0;
            $label = 'Empty';
        } else {
            $value = $field_label = sanitize_text_field( $_POST['value'] );
        }
		$storeCode = sanitize_text_field( $_POST['value'] );
		$storeAddress = get_option($storeCode);
        // Update session variable
        WC()->session->set( $field_id, $value );
		WC()->session->set( 'session_store_code', $storeCode );
		WC()->session->set( 'session_store_address', $storeAddress );

        // Send back the data to javascript (json encoded)
        echo esc_attr( $field_label ) . ' | ' . esc_attr($field_options[$value]);
        die();
    }
}
// Conditional function for validation
function bloyal_has_carrier_field(){
    $settings = bloyal_carrier_settings();
    if(!empty($settings['targeted_methods'][0])){
       return array_intersect(WC()->session->get( 'chosen_shipping_methods' ), $settings['targeted_methods']);
    }
}

// Validate the custom selection field
add_action('woocommerce_checkout_process', 'bloyal_carrier_company_checkout_validation');
function bloyal_carrier_company_checkout_validation() {
    // Load settings and convert them in variables
    extract( bloyal_carrier_settings() );
	$click_and_collect_label  = "Please select a ". get_option( 'bloyal_click_collect_label' ).".";
    if( bloyal_has_carrier_field() && isset( $_POST[$field_id] ) && empty( $_POST[$field_id] ) )
        wc_add_notice(
            sprintf( __( $click_and_collect_label ,"woocommerce"),
            '<strong>' . $field_label . '</strong>'
        ), "error" );
}

// Save custom field as order meta data
add_action( 'woocommerce_checkout_create_order', 'bloyal_save_carrier_company_as_order_meta', 30, 1 );
function bloyal_save_carrier_company_as_order_meta( $order ) {
    // Load settings and convert them in variables
    extract( bloyal_carrier_settings() );
	$store_pickup_address = sanitize_text_field( $field_options[ sanitize_key( $_POST[ $field_id ] ) ] ) . ', ' . esc_html( WC()->session->get( 'session_store_address' ) );
    if( bloyal_has_carrier_field() && isset( $_POST[$field_id] ) && ! empty( $_POST[$field_id] ) ) {
        $order->update_meta_data( '_'.$field_id, $store_pickup_address );
        WC()->session->__unset( $field_id ); // remove session variable
    }
}
// Display custom field in admin order pages
add_action( 'woocommerce_admin_order_data_after_shipping_address', 'bloyal_admin_order_display_carrier_company', 30, 1 );
function bloyal_admin_order_display_carrier_company( $order ) {
    // Load settings and convert them in variables
    extract( bloyal_carrier_settings() );

    $carrier = $order->get_meta( '_'.$field_id ); // Get carrier company

    if( ! empty($carrier) ) {
        // Display
		$field_label = get_option( 'bloyal_click_collect_label' );
        echo '<p><strong>' . esc_attr( $field_label ) . '</strong>: ' . esc_attr( $carrier ) . '</p>';
    }
}

// Display carrier company after shipping line everywhere (orders and emails)
add_filter( 'woocommerce_get_order_item_totals', 'bloyal_display_carrier_company_on_order_item_totals', 1000, 3 );
function bloyal_display_carrier_company_on_order_item_totals( $total_rows, $order, $tax_display ){
    // Load settings and convert them in variables
    extract( bloyal_carrier_settings() );

    $carrier = $order->get_meta( '_'.$field_id ); // Get carrier company

    if( ! empty($carrier) ) {
        $new_total_rows = [];
        // Loop through order total rows
        foreach( $total_rows as $key => $values ) {
            $new_total_rows[$key] = $values;
            // Inserting the carrier company under shipping method
            if( $key === 'shipping' ) {
                $new_total_rows[$field_id] = array(
                    'label' => $field_label,
                    'value' => $carrier,
                );
            }
        }
        return $new_total_rows;
    }
    return $total_rows;
}
//end Pickup at Store functionality

//bLoyal log download function
add_action( 'wp_ajax_bloyal_log_download', 'bloyal_log_download' ); // Log download on bLoyal admin
add_action( 'wp_ajax_nopriv_bloyal_log_download', 'bloyal_log_download' ); 
function bloyal_log_download() {
	$filename = BLOYAL_UPLOAD_DIR_BASEPATH.'/bLoyal_log_file.txt';
	if(file_exists($filename)) {
		//Define header information
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: 0");
		header('Content-Disposition: attachment; filename="'.basename($filename).'"');
		header('Content-Length: ' . filesize($filename));
		header('Pragma: public');
		//Clear system output buffer
		flush();
		//Read the size of the file
		readfile($filename);
		
	}
}
//End bLoyal log download function

//bLoyal log delete function
add_action( 'wp_ajax_bloyal_log_delete', 'bloyal_log_delete' ); 
add_action( 'wp_ajax_nopriv_bloyal_log_delete', 'bloyal_log_delete' );
function bloyal_log_delete(){
  $filename = BLOYAL_UPLOAD_DIR_BASEPATH.'/bLoyal_log_file.txt';
  if(file_exists($filename)) {

		$delete_log = wp_delete_file( $filename );
		
		$response = wp_send_json(
						array(
							'log_status' => true,
							'message' => 'Log file has been deleted.',
						)
			     );     
	}else{
		$response = wp_send_json(
						array(
							'log_status' => false,
							'message' => 'Log file not available..',
						)
					);		
	}
}

// register the ajax action for authenticated users
add_action('wp_ajax_bloyal_remove_custom_coupon', 'bloyal_remove_custom_coupon');

// register the ajax action for unauthenticated users
add_action('wp_ajax_nopriv_bloyal_remove_custom_coupon', 'bloyal_remove_custom_coupon');

// handle the ajax request
function bloyal_remove_custom_coupon() {
    $bloyal_obj = new BloyalController();
	$bloyal_obj->bloyal_remove_all_bloyal_coupons();
}

// add_filter( 'user_register', 'get_bloyal_session_registration');
// function get_bloyal_session_registration($user_id) {
// 	$current_user  = get_user_by('id', $user_id);
// 	$user_email = $current_user->data->user_email;
// 	get_bloyal_session($user_id, $user_email);
// }

// //add_action('wp_login', 'get_bloyal_session_login', 10, 2);
// function get_bloyal_session_login($user, $user_login) {
// 	$user_email = $user_login->user_email;
// 	$user_id = $user->ID;
// 	get_bloyal_session($user_id, $user_email);
// }

// function get_bloyal_session($user_id, $user_email) {
// 	$bloyal_snippets_obj = new BloyalSnippetsController();
// 	$bloyal_obj          = new BloyalController();
// 	$device_context = $bloyal_snippets_obj->send_curl_request('', 'contextdevices', 'loyaltyengine', 0);
// 	$snippet_device = $device_context->data->Code;
// 	$snippet_domain = get_option('bloyal_domain_name');
// 	$snippets_api_url = get_option('web_snippets_api_url');
// 	$loyalty_engine_api_url = get_option('loyalty_engine_api_url');
// 	$access_key = get_option('bloyal_access_key');
// 	$customer_uid = $bloyal_obj->bloyal_fetch_customer_uid( $user_id, $user_email );
// 	if (empty($customer_uid)) {
// 		// code to add dob in customer data
// 		$customer_birth_date = '';
// 		if(WC()->session->get('billing_birth_date') != ''){
// 			$customer_birth_date = WC()->session->get('billing_birth_date');
// 		}
// 		$action = 'customers/commands/signups';
// 		$user_details = array('Customer' => array('EmailAddress' => $user_email), 'BirthDate' => $customer_birth_date, 'DeviceCode' => $snippet_device);
		
// 	    bLoyalLoggerService::write_custom_log( "Customer Signups Request \n\r" . wp_json_encode( $user_details ) . "\r\n ======================\r\n", 1 );
		
// 		$response = $bloyal_snippets_obj->send_curl_request( $user_details, $action, 'loyaltyengine', 1 );
// 		bLoyalLoggerService::write_custom_log( "Customer Signups Response \n\r" . wp_json_encode( $response ) . "\r\n ======================\r\n", 1 );
// 	}
	
// }
// code to disable store payment method on checkout page if user is not logged
function rp_filter_gateways( $args ) {
	if( !is_user_logged_in() && isset($args['bloyal_stored_payment_method']) ) {
	unset( $args['bloyal_stored_payment_method'] );
	}
	return $args;
	}
	add_action( 'woocommerce_available_payment_gateways', 'rp_filter_gateways' );
?>
