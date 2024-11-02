<?php
defined( 'ABSPATH' ) or die( 'No script!' );

require_once BLOYAL_DIR . '/app/controller/bloyal_logger_service.php';

if ( ! class_exists( 'LoyaltyDollarPaymentController' ) ) {

	class LoyaltyDollarPaymentController {
		private $bloyal_controller_obj;
		private $loyalty_mapped_tender_code;

		function __construct() {
			$this->bloyal_controller_obj      = new BloyalController();
			$this->loyalty_mapped_tender_code = null;
			$this->access_key                 = get_option( 'bloyal_access_key' );
			$bLoyalTenderPaymentMapping       = get_option( 'bloyal_tender_payments_mapping' );
			if ( isset( $bLoyalTenderPaymentMapping['bloyal_loyalty_dollar'] ) ) {
				$this->loyalty_mapped_tender_code = $bLoyalTenderPaymentMapping['bloyal_loyalty_dollar'];
			}
		}

		public function getLoyaltyDollarBalance() {
			try {
				if ( $this->loyalty_mapped_tender_code ) {
					$soapServiceClient = $this->bloyal_controller_obj->bloyal_get_api_url( 'paymentengine' );
					$result            = $soapServiceClient->GetCardBalance( $this->makeLoyaltyDollarCheckBalanceQuery() );
					bLoyalLoggerService::write_custom_log( "Loyalty Dollar Balance Response\r\n" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
					if ( isset( $result->GetCardBalanceResult->Status ) && $result->GetCardBalanceResult->Status == 'Approved' ) {
						return $result;
					}
				}
				return -1;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}

		public function redeemLoyaltyDollarBalance( $amount, $order_id ) {
			try {
				$use_order_engine           = get_option( 'bloyal_use_order_engine' );
				$soapServiceClient          = $this->bloyal_controller_obj->bloyal_get_api_url( 'paymentengine' );
				$redeemLoyaltyDollarRequest = $this->makeLoyaltyDollarRedeemBalanceQuery( $amount, $order_id );
				bLoyalLoggerService::write_custom_log( "Loyalty Dollar Balance Redeem Request\r\n" . wp_json_encode( $redeemLoyaltyDollarRequest ) . "\r\n ======================\r\n", 1 );
				if ( $use_order_engine == 'true' ) {
					$result = $soapServiceClient->CardAuthorize( $redeemLoyaltyDollarRequest );
				} else {
					$result = $soapServiceClient->CardRedeem( $redeemLoyaltyDollarRequest );
				}
				bLoyalLoggerService::write_custom_log( "Loyalty Dollar Balance Redeem response\r\n" . wp_json_encode( $result ) . "\r\n ======================\r\n", 1 );
				if ( isset( $result->CardRedeemResult->Status ) && $result->CardRedeemResult->Status == 'Approved' ) {
					add_post_meta( $order_id, '_loyalty_dollars_amount', $amount, true );
					add_post_meta( $order_id, '_loyalty_dollars_transaction_id', $result->CardRedeemResult->TransactionCode, true );
					return array(
						'result'         => true,
						'transaction_id' => $result->CardRedeemResult->TransactionCode,
						'auth_code'      => '',
					);
				}
				if ( isset( $result->CardAuthorizeResult->Status ) && $result->CardAuthorizeResult->Status == 'Approved' ) {
					add_post_meta( $order_id, '_loyalty_dollars_amount', $amount, true );
					add_post_meta( $order_id, '_loyalty_dollars_transaction_id', $result->CardAuthorizeResult->TransactionCode, true );
					return array(
						'result'         => true,
						'transaction_id' => $result->CardAuthorizeResult->TransactionCode,
						'auth_code'      => $result->CardAuthorizeResult->AuthorizationCode,
					);
				}
				return array(
					'result'         => false,
					'transaction_id' => null,
				);

			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
		
		/**
		 * Initialize makeLoyaltyDollarCheckBalanceQuery.
		 *
		 * @return void
		 */
		private function makeLoyaltyDollarCheckBalanceQuery() {
			try {
				$customer_uid = '';
				if ( is_user_logged_in() ) {
					$user         = wp_get_current_user();
					$customer_uid = $this->bloyal_controller_obj->bloyal_fetch_customer_uid( $user->ID, $user->user_email );
				}
				$loyalty_dollar_balance_request = array(
					'deviceAccessKey' => $this->access_key,
					'storeCode'       => '',
					'deviceCode'      => '',
					'request'         => array(
						'CardNumber'            => '',
						'CardPin'               => '',
						'CustomerUid'           => $customer_uid,
						'TenderCode'            => $this->loyalty_mapped_tender_code,
						'Swiped'                => false,
						'TransactionExternalId' => '',
						'TransactionToken'      => '',
					),
				);
				bLoyalLoggerService::write_custom_log( "Loyalty Dollar Balance Request\r\n" . wp_json_encode( $loyalty_dollar_balance_request ) . "\r\n ======================\r\n", 1 );
				return $loyalty_dollar_balance_request;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
		
		/**
		 * Initialize makeLoyaltyDollarRedeemBalanceQuery.
		 *
		 * @param  float $amount as parameter.
		 * @param  int $order_id as parameter.
		 * @return array
		 */
		private function makeLoyaltyDollarRedeemBalanceQuery( $amount, $order_id ) {
			try {
				$use_order_engine = get_option( 'bloyal_use_order_engine' );
				$order            = new WC_Order( $order_id );
				$user_id          = $order->get_user_id();
				$customer         = new WC_Customer( $user_id );
				$billing_email    = $customer->get_billing_email();
				$customer_uid     = $this->bloyal_controller_obj->bloyal_fetch_customer_uid( $user_id, $billing_email );
				$request_content  = array(
					'deviceAccessKey' => $this->access_key,
					'storeCode'       => '',
					'deviceCode'      => '',
					'request'         => array(
						'Amount'                => ( 'true' === $use_order_engine ) ? -$amount : $amount,
						'CustomerUid'           => $customer_uid,
						'TenderCode'            => $this->loyalty_mapped_tender_code,
						'Swiped'                => false,
						'TransactionExternalId' => $order_id,
					),
				);
				return $request_content;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
	}
}
