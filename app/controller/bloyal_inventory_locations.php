<?php

require_once BLOYAL_DIR . '/app/controller/class-bloyalcontroller.php';
require_once BLOYAL_DIR . '/app/view/bloyal_view.php';

if ( ! class_exists( 'BloyalInventoryLocations' ) ) {
	/**
	 * BloyalInventoryLocations
	 */
	class BloyalInventoryLocations {
		/**
		 * Initalize bloyal_controller_obj.
		 *
		 * @var mixed bloyal_controller_obj.
		 */
		private $bloyal_controller_obj;
		/**
		 * Initialize __construct
		 *
		 * @return void
		 */
		public function __construct() {
			$this->bloyal_controller_obj = new BloyalController();
		}
		/**
		 * Function to call the bLoyal aprrove cart API.
		 *
		 * @return boolean
		 */
		public function get_bloyal_inventory_locations() {
			try {
				$action   = 'InventoryLocations';
				$response = $this->bloyal_controller_obj->send_curl_request( '', $action, 'grid', 0 );
				if ( isset( $response ) && isset( $response->status ) ) {
					if ( 'success' === $response->status ) {
						$inventory_locations = array(
							'message' => $response->data,
							'status'  => 'success',
						);
						return wp_json_encode( $inventory_locations );
					} else {
						$inventory_locations = array(
							'message' => $response->message,
							'status'  => 'error',
						);
						return wp_json_encode( $inventory_locations );
					}
				} else {
					$inventory_locations = array(
						'message' => 'Unable to fetch Inventory Locations.',
						'status'  => '',
					);
					return wp_json_encode( $inventory_locations );
				}
			} catch ( Exception $e ) {
				$this->log( __FUNCTION__, 'Error in save multiple shipping addresses Reason: ' . $e->getMessage() );
				return $e->getMessage();
			}
		}
	}
}
