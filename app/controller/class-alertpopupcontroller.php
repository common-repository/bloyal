<?php

defined( 'ABSPATH' ) or die( 'No script!' );

if ( !class_exists('BLOYAL_AlertPopUpController') ) {


	class BLOYAL_AlertPopUpController {

	    function __construct() {
			add_action( 'wp_ajax_get_bloyal_cart_items', array( $this, 'ajax_alert_data_on_update' ) );
			add_action( 'wp_ajax_nopriv_get_bloyal_cart_items', array( $this, 'ajax_alert_data_on_update' ) );

	    }

		public function process_alert_data($bloyalAlerts,$is_calculate_cart_call = "false"){
			try {
				if(!empty(WC()->session->get( 'bloyal_alerts_data'))) {
					$bloyalAlerts = WC()->session->get( 'bloyal_alerts_data');
				}
				$loginDomain                     = get_option( 'bloyal_domain_name' );
				$strDeviceCode                   = get_option( 'Device_Code' );
				$options_array = array(
					'bloyal_domain_name' => $loginDomain,
					'bloyal_device_code' => $strDeviceCode,
				); 
				$this->display_alert_pop_up($bloyalAlerts,$is_calculate_cart_call, $options_array);
				return;
			} catch (Exception $ex) {
				bLoyalLoggerService::write_custom_log($ex->getMessage(),3);
			}
		}

		public function get_alert_html_content( $cart_uid, $alert_uid, $alert_category, $line_uid, $snippet_code ) {
			try {
				
				$loginDomain                     = get_option( 'bloyal_domain_name' );
				$strDeviceCode                   = get_option( 'Device_Code' );
				$bloyal_snippet_args = array();
				$bloyal_snippet_args['CartUid']             = $cart_uid;
				$bloyal_snippet_args['AlertUid']            = $alert_uid;
				$bloyal_snippet_args['LineUid']             = $line_uid;
				$bloyal_snippet_args['CashierCode']         = "";
				$bloyal_snippet_args['OnSnippetComplete']   = "EgiftSnippetComplete";
				$bloyal_snippet_args['PaymentRedirectToHome']   = true;
				$snippets_api_url = get_option( 'web_snippets_api_url' );
                $snippets_src     = "https://snippets.bloyal.io/bLoyalSnippetLoader.js?ver=1.0.0";
				$alert_snippet_div             =  "<div data-bloyal-snippet-code='".$snippet_code."' data-bloyal-login-domain='".$loginDomain."' data-bloyal-device-code='".$strDeviceCode."' data-bloyal-snippet-args='".wp_json_encode($bloyal_snippet_args, TRUE)."' id='root'></div><script> function EgiftSnippetComplete() { console.log('The Snippet is done') } </script>";;
				return $alert_snippet_div;
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}

		
		function display_alert_pop_up($alertsData, $is_calculate_cart_call, $options_array) {
			try {
				if (is_page( 'cart' ) || is_page( 'checkout' )) {
				    $strCurrentPage = is_page( 'cart' ) ? 'cart' : 'checkout';
					$snippets_api_url = get_option( 'web_snippets_api_url' );
					$snippets_src     = str_replace( 'Web', '', $snippets_api_url ) . '/bLoyalSnippetLoader.js';
					$snippets_src     = "https://snippets.bloyal.io/bLoyalSnippetLoader.js?ver=1.0.0";
					wp_enqueue_script('jquery-ui-dialog');
		    		wp_enqueue_style('wp-jquery-ui-dialog');
					wp_enqueue_script( 'bloyal_alerts_js', BLOYAL_URL . 'assets/js/alerts.js?version=2.9.1', array('jquery', 'jquery-ui-dialog'), '2.9.1', true );
					wp_localize_script( 'bloyal_alerts_js', 'alertArray', json_encode( $alertsData ) );
					wp_localize_script( 'bloyal_alerts_js', 'optionsArray', json_encode( $options_array ) );
					wp_localize_script( 'bloyal_alerts_js', 'httpAdminUrl', admin_url( 'admin-ajax.php' ) );
					wp_localize_script( 'bloyal_alerts_js', 'currentPage', $strCurrentPage );
					wp_localize_script( 'bloyal_alerts_js', 'snippets_js', $snippets_src );
				}
			} catch (Exception $ex) {
				bLoyalLoggerService::write_custom_log($ex->getMessage(),3);
			}
		}


		function ajax_alert_data_on_update() {
			try {
				$arrAlertData = WC()->session->get( 'bloyal_alerts_data' );
				WC()->session->set( 'bloyal_alerts_data', null );
				WC()->session->__unset( 'bloyal_alerts_data' );
				WC()->session->set( 'bloyal_cart_alerts_data', null );
				WC()->session->__unset( 'bloyal_cart_alerts_data' );
				$alertsData = array(
								'status' => "success",
								'html'  => "Alert is done.",
							);
				wp_send_json( json_encode( $alertsData ) );
				wp_die();
			} catch ( Exception $ex ) {
				bLoyalLoggerService::write_custom_log( $ex->getMessage(), 3 );
			}
		}
	}
}
