<?php
	defined( 'ABSPATH' ) or die( 'No script!' );

require_once BLOYAL_DIR . '/app/controller/class-alertpopupcontroller.php';
require_once BLOYAL_DIR . '/app/controller/bloyal_logger_service.php';

if ( ! class_exists( 'CustomTaxClassController' ) ) {

	class CustomTaxClassController {

		/**
		 * @param WC_Tax instance
		 * @return object
		 */
		private $obj_WC_Tax_Class;


		private $obj_alert;
		/**
		 * @param $tax_rate_ids
		 * @return array
		 */
		private $tax_rate_ids;

		/**
		 * @param $shipping_taxes_rate_ids
		 * @return array
		 */
		private $shipping_taxes_rate_ids;


		/**
		 * @param $applied_shipping_taxes
		 * @return array
		 */
		public $applied_shipping_taxes;

		/**
		 * @param $applied_shipping_taxes
		 * @return array
		 */
		private $chosen_shipping_cost;
		/**
		 * __construct
		 * class constructor will set the needed filter and action hooks
		 */
		function __construct() {
			$this->obj_WC_Tax_Class = new WC_Tax();
			$this->obj_alert        = new BLOYAL_AlertPopUpController();
		}

		/**
		 * @param integer $tax_rate_id
		 * @param integer $tax_rate
		 * @return void
		 */
		public function update_wooco_tax_rate( $tax_rate_id, $tax_rate ) {
			try {
				$tax_rate_array = array( 'tax_rate' => $tax_rate );
				$this->obj_WC_Tax_Class->_update_tax_rate( $tax_rate_id, $tax_rate_array );
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
		/**
		 * Insert a new tax rate.
		 *
		 * @param  float  $tax_rate
		 * @param  string $tax_rate_name
		 *  @param  string $tax_class
		 * @return int tax rate id
		 */
		public function _insert_tax_rate( $tax_rate, $tax_rate_name, $tax_class ) {
			try {
				global $wpdb;
				$data = $wpdb->query( $wpdb->prepare( 
					"INSERT INTO {$wpdb->prefix}woocommerce_tax_rates ( tax_rate_name,
					tax_rate_priority, tax_rate_compound,
					tax_rate_shipping, tax_rate, tax_rate_class
					) VALUES ( %s,%s,%s,%s,%f,%s) ", 
					$tax_rate_name, '1','false', '1', $tax_rate, $tax_class
				) );

				return $wpdb->insert_id;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}

		/**
		 * Get all tax classes
		 *
		 * @return array $taxClasses
		 */
		public function get_tax_classes() {

			return $this->obj_WC_Tax_Class()->get_tax_classes();
		}

		/**
		 * Get tax class
		 *
		 * @param string $tax_rate_class
		 * @return stdObject $rates
		 */
		public function check_is_tax_rate_exist( $tax_rate_class ) {
			try {
				global $wpdb;
				$rates = $wpdb->get_results( $wpdb->prepare( "SELECT tax_rate_id,tax_rate,tax_rate_class FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class=%s ", $tax_rate_class ) );
				return $rates;
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}

		}

		/**
		 * Calculate tax rate using bloyal tax and apply to wc_cart of line items
		 *
		 * @param WC_Cart   $wc_cart_object
		 * @param stdObject $bloyal_cart_object
		 * @return void
		 */

		public function calculate_and_apply_custom_tax( $wc_cart_object, $bloyal_cart_object ) {
			try {
				$this->applied_shipping_taxes = array();
				$this->create_or_update_tax_rate( $bloyal_cart_object, $wc_cart_object->get_cart() );
				if ( class_exists( 'WC_Cart_Totals' ) ) {
					do_action( 'woocommerce_cart_reset', $wc_cart_object, false );
					new WC_Cart_Totals( $wc_cart_object );
				}
				foreach ( $wc_cart_object->get_cart() as $cart_key => $cart_item ) {
					WC()->session->set( 'applied_line_taxes', $this->tax_rate_ids );
					if ( $cart_item['data']->is_type( 'variation' ) ) {
						add_filter( 'woocommerce_product_variation_get_tax_class', 'bloyal_override_variation_product_tax_class', 10, 2 );
					}
					add_filter( 'woocommerce_product_get_tax_class', 'bloyal_override_product_tax_class', 10, 2 );
					if ( ! empty( $this->shipping_taxes_rate_ids ) ) {
						if ( $this->chosen_shipping_cost > 0 ) {
							if ( empty( $this->applied_shipping_taxes ) ) {
								$this->applied_shipping_taxes[ $this->shipping_taxes_rate_ids[ $cart_key ]['rateId'] ] = $this->chosen_shipping_cost;
							}
						}
					}
				}
				WC()->session->set( 'applied_shipping_taxes', $this->applied_shipping_taxes );
				add_filter( 'woocommerce_shipping_rate_taxes', 'bloyal_shipping_tax_rate', 10, 2 );

			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}

		/**
		 * create or update tax rate in woocommerce
		 *
		 * @param WC_Cart   $wc_cart_object
		 * @param stdObject $bloyal_cart_object
		 * @return void
		 */

		public function create_or_update_tax_rate( $bloyal_cart_object, $wc_cart_object ) {
			try {
				$bloyalLines  = $bloyal_cart_object->data->Cart->Lines;
				$bloyalAlerts = $bloyal_cart_object->data->LoyaltySummary->Alerts;
				if ( ! empty( $bloyalAlerts ) ) {
				}
				$this->chosen_shipping_cost = 0;
				$shippmentTaxData           = $bloyal_cart_object->data->Cart->Shipments[0]->TaxDetails;
				if ( ! empty( $shippmentTaxData ) ) {
					if ( count( $shippmentTaxData ) > 1 ) {
						foreach ( $shippmentTaxData as $taxKey => $taxValue ) {
							$this->chosen_shipping_cost += $taxValue->Amount;
						}
					} else {
						if ( isset( $shippmentTaxData[0] ) && ! empty( $shippmentTaxData[0] ) ) {
							$shippmentTaxData = $shippmentTaxData[0];
						}
						$this->chosen_shipping_cost = $shippmentTaxData->Amount;
					}
					if ( $this->chosen_shipping_cost > 0 ) {
						$this->chosen_shipping_cost = round( $this->chosen_shipping_cost, 2 );
					}
				}
				if ( ! empty( $bloyalLines ) ) {
					$selectedShippingMethod = $bloyal_cart_object->data->Cart->Shipments[0];
					foreach ( $bloyalLines as $line_key => $line ) {
						$floatBlylTaxRate = 0;
						if ( ! empty( $line->TaxDetails ) ) {
							$line_total_price     = $line->ExtendedNetPrice;
							$strBlylTaxClassName  = '';
							$strBlylTaxClassTitle = '';
							$objArrLineTaxes      = $line->TaxDetails;
							if ( count( $objArrLineTaxes ) > 1 ) {
								foreach ( $objArrLineTaxes as $taxKey => $tax ) {
									$tax_amount           = (float) $tax->Amount;
									$floatBlylTaxRate    += (float) ( $tax_amount / $line_total_price ) * 100;
									$strBlylTaxClassTitle = $strBlylTaxClassTitle != '' ? $strBlylTaxClassTitle . '-' . preg_replace( '/[^A-Za-z0-9]/', '', $tax->Code ) : preg_replace( '/[^A-Za-z0-9]/', '', $tax->Code );
									$strBlylTaxClassCode  = preg_replace( '/[^A-Za-z0-9]/', '', $tax->ClassCode );
								}
								$strBlylTaxClassName = $strBlylTaxClassCode != '' ? $strBlylTaxClassCode . '-' . $strBlylTaxClassTitle : $strBlylTaxClassTitle;
							} else {
								if ( isset( $objArrLineTaxes[0] ) && ! empty( $objArrLineTaxes[0] ) ) {
									$objArrLineTaxes = $objArrLineTaxes[0];
								}
								$floatBlylTaxRate     = (float) $objArrLineTaxes->Rate * 100;
								$strBlylTaxClassCode  = preg_replace( '/[^A-Za-z0-9]/', '', $objArrLineTaxes->ClassCode );
								$strBlylTaxClassTitle = preg_replace( '/[^A-Za-z0-9]/', '', $objArrLineTaxes->Code );
								$strBlylTaxClassName  = $strBlylTaxClassCode != '' ? $strBlylTaxClassCode . '-' . $strBlylTaxClassTitle : $strBlylTaxClassTitle;
							}
							$objRates = $this->check_is_tax_rate_exist( $strBlylTaxClassName );
							if ( ! empty( $objRates ) ) {
								if ( (float) $objRates[0]->tax_rate != $floatBlylTaxRate ) {
									$this->update_wooco_tax_rate( $objRates[0]->tax_rate_id, $floatBlylTaxRate );
								}
								if ( isset( $wc_cart_object[ $line->ExternalId ]['data'] ) ) {
									$this->tax_rate_ids[ $wc_cart_object[ $line->ExternalId ]['data']->get_id() ] = $strBlylTaxClassName;
								}
								if ( ! empty( $selectedShippingMethod->TaxDetails ) && $selectedShippingMethod->TaxDetails[0]->Amount ) {
									$this->shipping_taxes_rate_ids[ $line->ExternalId ] = array(
										'rateId' => $objRates[0]->tax_rate_id,
										'rate'   => $floatBlylTaxRate,
									);
								}
							} else {
								$taxRateId = $this->_insert_tax_rate( $floatBlylTaxRate, 'Taxes', $strBlylTaxClassName );
								if ( isset( $wc_cart_object[ $line->ExternalId ]['data'] ) ) {
									$this->tax_rate_ids[ $wc_cart_object[ $line->ExternalId ]['data']->get_id() ] = $strBlylTaxClassName;
								}
								if ( ! empty( $selectedShippingMethod->TaxDetails ) && $selectedShippingMethod->TaxDetails[0]->Amount ) {
									$this->shipping_taxes_rate_ids[ $line->ExternalId ] = array(
										'rateId' => $taxRateId,
										'rate'   => $floatBlylTaxRate,
									);
								}
							}
						}
					}
				}
			} catch ( Exception $e ) {
				bLoyalLoggerService::write_custom_log( $e->getMessage(), 3 );
			}
		}
	}
}
