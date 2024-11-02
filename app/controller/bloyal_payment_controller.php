<?php
// If this file is called directly, abort.
defined( 'ABSPATH' ) or die( 'No script!' );

if ( ! class_exists( 'PaymentController' ) ) {

	class PaymentController {

		private $bloyalControllerObj;
		private $access_key;
		private $loyaltyMappedTenderCode;

		function __construct() {
			$this->bloyalControllerObj     = new BloyalController();
			$this->access_key              = get_option( 'bloyal_access_key' );
			$this->loyaltyMappedTenderCode = null;
			$bLoyalTenderPaymentMapping    = get_option( 'bloyal_tender_payments_mapping' );
			if ( isset( $bLoyalTenderPaymentMapping['bloyal_gift_card'] ) ) {
				$this->loyaltyMappedTenderCode = $bLoyalTenderPaymentMapping['bloyal_gift_card'];
			}
		}

		public function getGiftcardBalance( $cardNumber ) {
			try {

				$soapServiceClient = $this->bloyalControllerObj->bloyal_get_api_url( 'paymentengine' );
				$result            = $soapServiceClient->GetCardBalance( $this->makeGiftcardCheckBalanceQuery( $cardNumber ) );
				bLoyalLoggerService::write_custom_log( "Gift Card Balance Response\r\n" . json_encode( $result ) . "\r\n ======================\r\n", 1 );
				if ( isset( $result->GetCardBalanceResult->Status ) && $result->GetCardBalanceResult->Status == 'Approved' ) {
					return $result;
				}
				return -1;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}

		public function redeemGiftCardBalance( $cardNumber, $amount, $order_id ) {
			try {
				$use_order_engine      = get_option( 'bloyal_use_order_engine' );
				$soapServiceClient     = $this->bloyalControllerObj->bloyal_get_api_url( 'paymentengine' );
				$redeemGiftCartRequest = $this->makeGiftcardRedeemBalanceQuery( $cardNumber, $amount, $order_id );
				bLoyalLoggerService::write_custom_log( "Gift Card Balance Redeem Request\r\n" . json_encode( $redeemGiftCartRequest ) . "\r\n ======================\r\n", 1 );
				if ( $use_order_engine == 'true' ) {
					$result = $soapServiceClient->CardAuthorize( $redeemGiftCartRequest );
				} else {
					$result = $soapServiceClient->CardRedeem( $redeemGiftCartRequest );
				}
				bLoyalLoggerService::write_custom_log( "Gift Card Balance Redeem Response\r\n" . json_encode( $result ) . "\r\n ======================\r\n", 1 );
				if ( isset( $result->CardRedeemResult->Status ) && $result->CardRedeemResult->Status == 'Approved' ) {
					add_post_meta( $order_id, '_gift_card_number', $cardNumber, true );
					add_post_meta( $order_id, '_gift_card_amount', $amount, true );
					add_post_meta( $order_id, '_gift_card_transaction_id', $result->CardRedeemResult->TransactionCode, true );
					return array(
						'balance'        => $result->CardRedeemResult->CurrentBalance,
						'transaction_id' => $result->CardRedeemResult->TransactionCode,
						'result'         => 'success',
					);
				}
				if ( isset( $result->CardAuthorizeResult->Status ) && $result->CardAuthorizeResult->Status == 'Approved' ) {
					add_post_meta( $order_id, '_gift_card_number', $cardNumber, true );
					add_post_meta( $order_id, '_gift_card_amount', $amount, true );
					add_post_meta( $order_id, '_gift_card_transaction_id', $result->CardAuthorizeResult->TransactionCode, true );
					return array(
						'balance'        => $result->CardAuthorizeResult->CurrentBalance,
						'transaction_id' => $result->CardAuthorizeResult->TransactionCode,
						'auth_code'      => $result->CardAuthorizeResult->AuthorizationCode,
						'result'         => 'success',
					);
				}
				return -1;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}

		public function authorizeGiftCardBalance( $cardNumber, $amount, $order_id ) {
			try {
				$soapServiceClient     = $this->bloyalControllerObj->bloyal_get_api_url( 'paymentengine' );
				$redeemGiftCartRequest = $this->makeGiftcardRedeemBalanceQuery( $cardNumber, $amount, $order_id );
				bLoyalLoggerService::write_custom_log( "Gift Card Balance Redeem Request\r\n" . json_encode( $redeemGiftCartRequest ) . "\r\n ======================\r\n", 1 );
				$result = $soapServiceClient->CardAuthorize( $redeemGiftCartRequest );
				bLoyalLoggerService::write_custom_log( "Gift Card Balance Redeem Response\r\n" . json_encode( $result ) . "\r\n ======================\r\n", 1 );
				if ( isset( $result->CardRedeemResult->Status ) && $result->CardRedeemResult->Status == 'Approved' ) {
					add_post_meta( $order_id, '_gift_card_number', $cardNumber, true );
					add_post_meta( $order_id, '_gift_card_amount', $amount, true );
					add_post_meta( $order_id, '_gift_card_transaction_id', $result->CardRedeemResult->TransactionCode, true );
					return array(
						'balance'        => $result->CardRedeemResult->CurrentBalance,
						'transaction_id' => $result->CardRedeemResult->TransactionCode,
						'result'         => 'success',
					);
				}
				return -1;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}

		private function makeGiftcardCheckBalanceQuery( $giftCardNumber ) {
			try {
				$gift_card_request = array(
					'deviceAccessKey' => $this->access_key,
					'storeCode'       => '',
					'deviceCode'      => '',
					'request'         => array(
						'CardNumber'            => $giftCardNumber,
						'CardPin'               => '',
						'TenderCode'            => $this->loyaltyMappedTenderCode,
						'Swiped'                => false,
						'TransactionExternalId' => '',
						'TransactionToken'      => '',
					),
				);
				bLoyalLoggerService::write_custom_log( "Gift Card Balance Request\r\n" . json_encode( $gift_card_request ) . "\r\n ======================\r\n", 1 );
				return $gift_card_request;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}

		private function makeGiftcardRedeemBalanceQuery( $giftCardNumber, $amount, $order_id ) {
			try {
				$order           = new WC_Order( $order_id );
				$user_id         = $order->get_user_id();
				$customer        = new WC_Customer( $user_id );
				$billing_email   = $customer->get_billing_email();
				$customer_uid    = $this->bloyalControllerObj->bloyal_fetch_customer_uid( $user_id, $billing_email );
				$request_content = array(
					'deviceAccessKey' => $this->access_key,
					'storeCode'       => '',
					'deviceCode'      => '',
					'request'         => array(
						'Amount'                => $amount,
						'CardNumber'            => $giftCardNumber,
						'CardPin'               => '',
						'CustomerUid'           => $customer_uid,
						'TenderCode'            => $this->loyaltyMappedTenderCode,
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
