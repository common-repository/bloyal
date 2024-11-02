<?php
if ( ! class_exists( 'bLoyalLoggerService' ) ) {
    function bloyal_log_settings() {
		$log_enable = get_option( 'bloyal_log_enable_disable' );
	    return $log_enable;
	}
	class bLoyalLoggerService {
		private static $log_file_name = BLOYAL_UPLOAD_DIR_BASEPATH . '/bLoyal_log_file.txt';      private static $logtype         = array(
			1 => 'Info',
			2 => 'Warning',
			3 => 'Exception',
		);
		private static $log_delimeter = '====================================';
		private $max_size;
		private $log_file_data;
		private $domain_name;
		private $access_key;
		public static $log_enable = null;

		public function __construct() {

			$this->max_size        = 250000;
			$this->domain_name     = get_option( 'bloyal_domain_name' );
			$this->access_key      = get_option( 'bloyal_access_key' );
			$this->logging_api_url = get_option( 'logging_api_url' );
		}

		public static function write_custom_log( $log, $type = 1 ) {
			try {
				self::$log_enable = bloyal_log_settings();
				if( 'true' === self::$log_enable) {
					$logfile  = fopen( self::$log_file_name, 'a' );
					$log_data = 'Log: 	' . self::$logtype[ $type ] . "\r\n" .
								'File: 	' . debug_backtrace()[0]['file'] . "\r\n" .
								'Line:	' . debug_backtrace()[0]['line'] . "\r\n" .
								'Class: ' . debug_backtrace()[0]['class'] . "\r\n" .
								'Function: ' . debug_backtrace()[1]['function'] . "\r\n" .
								'Date time: ' . date( 'Y-m-d H:i:s A' ) . "\r\n" .
								$log . "\r\n" . self::$log_delimeter . "\r\n";

					fwrite( $logfile, $log_data );
					fclose( $logfile );
			   }
			} catch ( Exception $e ) {
;
			}
		}

		public function uploadLog() {
			try {
				if ( file_exists( self::$log_file_name ) && filesize( self::$log_file_name ) < $this->max_size ) {
					return false;
				}
				$this->readLogFile();
				if ( empty( $this->log_file_data ) ) {
					return false;
				}
				//this API use for create bLoyal logs by bLoyal
				$post_url = $this->logging_api_url . "/api/v4/{$this->access_key}/Logger/Multiple";
				$args     = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => wp_json_encode( $this->log_file_data ),
					'method'  => 'POST',
					'timeout' => 45,
				);
				$response        = wp_remote_post( $post_url, $args );
				$response_status = wp_remote_retrieve_response_code( $response );
				$response        = wp_remote_retrieve_body( $response );
				$test_response   = $response;
				$test_response   = json_decode( $test_response );
				if ( ! empty( $response ) && $test_response->status == 'success' ) {
					unlink( BLOYAL_UPLOAD_DIR_BASEPATH . '/bloyal_temp_log.txt' );
				}
				$result = json_decode( $response, true );
				if ( is_wp_error( $result ) ) {
					$error = $response->get_error_message();
					throw new Exception( $error, 1 );
				}
			} catch ( Exception $e ) {
				self::write_custom_log( $e->getMessage(), 3 );
			}
		}

		private function readLogFile() {
			try {
				self::$log_enable = bloyal_log_settings();
				if( 'true' === self::$log_enable) {
					$read_log_file = BLOYAL_UPLOAD_DIR_BASEPATH . '/bloyal_temp_log.txt';
					$log_temp_file = fopen( $read_log_file, 'a' );
					fwrite( $log_temp_file, file_get_contents( self::$log_file_name ) );
					fclose( $log_temp_file );
					unlink( self::$log_file_name );
					$handle              = fopen( BLOYAL_UPLOAD_DIR_BASEPATH . '/bloyal_temp_log.txt', 'r' );
					$this->log_file_data = array();
					$data                = $current_log_type = $file_name = $date_time = $function_name = '';
					while ( $line = fgets( $handle, 1000 ) ) {
						$line  = trim( $line );
						$data .= $line;
						if ( strpos( $line, 'Log:' ) === 0 ) {
							foreach ( self::$logtype as $key => $type ) {
								if ( strpos( $line, $type ) !== false ) {
									$current_log_type = $type;
								}
							}
						}
						if ( strpos( $line, 'File:' ) === 0 ) {
							$file_name = str_replace( 'File:', '', $line );
						}
						if ( strpos( $line, 'Date time:' ) === 0 ) {
							$date_time = str_replace( 'Date time:', '', $line );
						}
						if ( strpos( $line, 'Function:' ) === 0 ) {
							$function_name = str_replace( 'Function:', '', $line );
						}
						if ( strpos( $line, self::$log_delimeter ) !== false ) {
							$message_detail = array(
								str_replace( self::$log_delimeter, '', $data ),
							);
							$this->log_file_data[] = array(
								'ContextExternalId' => $file_name,
								'EntityTypeName'    => "Woocommerce Plugin {$current_log_type} Logs For function: {$function_name}.",
								'MessageFormat'     => 'Text',
								'MessageDetail'     => $message_detail,
								'EntityValue'       => array(),
								'EventType'         => $current_log_type,
								'Duration'          => '',
								'Message'           => "bLoyal Woocommerce Connector Logs for Client: {$this->domain_name}",
								'Created'           => $date_time,
								'Stack'             => '',
							);
							$data                  = $current_log_type = $file_name = $date_time = $function_name = '';
						}
					}
				}
			} catch ( Exception $e ) {
				self::write_custom_log( $e->getMessage(), 3 );
			}
		}
	}
}
