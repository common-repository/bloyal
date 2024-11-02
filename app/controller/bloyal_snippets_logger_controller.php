<?php

if ( ! class_exists( 'bLoyalSnipetsLoggerService' ) ) {
	function bloyal_snippet_log_settings() {
		$snippet_log_enable = get_option( 'bloyal_log_enable_disable' );
	    return $snippet_log_enable;
	}
	class bLoyalSnipetsLoggerService {
		private static $logFileName = BLOYAL_UPLOAD_DIR_BASEPATH . '/bLoyal_snippets_log_file.txt';     private static $logtype         = array(
			1 => 'Info',
			2 => 'Warning',
			3 => 'Exception',
		);
		private static $logDelimeter = '====================================';
		private $maxSize;
		private $logFileData;
		private $domainName;
		private $accessKey;
		public static $snippet_log_enable = null;

		public function __construct() {
			$this->maxSize       = 1;
			$this->domainName    = get_option( 'bloyal_domain_name' );
			$this->accessKey     = get_option( 'bloyal_snippets_access_key' );
			$this->loggingApiUrl = get_option( 'logging_api_url_snippet' );
		}

		public static function write_custom_log( $log, $type = 1 ) {
			try {
				self::$snippet_log_enable = bloyal_snippet_log_settings();
				if( 'true' === self::$snippet_log_enable) {
					$logfile = fopen( self::$logFileName, 'a' );
					$logData = 'Log: 	' . self::$logtype[ $type ] . "\r\n" .
								'File: 	' . debug_backtrace()[0]['file'] . "\r\n" .
								'Line:	' . debug_backtrace()[0]['line'] . "\r\n" .
								'Class: ' . debug_backtrace()[0]['class'] . "\r\n" .
								'Function: ' . debug_backtrace()[1]['function'] . "\r\n" .
								'Date time: ' . date( 'Y-m-d H:i:s A' ) . "\r\n" .
								$log . "\r\n" . self::$logDelimeter . "\r\n";

					fwrite( $logfile, $logData );
					fclose( $logfile );
				}
			} catch ( Exception $e ) {
				self::write_custom_log( $e->getMessage(), 3 );
			}
		}

		public function uploadLog() {
			try {
				if ( file_exists( self::$logFileName ) && filesize( self::$logFileName ) < $this->maxSize ) {
					return false;
				}
				$this->readLogFile();
				if ( empty( $this->logFileData ) ) {
					return false;
				}
				//this API user for bLoyal Logger service by bLoyal
				$post_url = $this->loggingApiUrl . "/api/v4/{$this->accessKey}/Logger/Multiple";
				$args = array(
					'headers' => array(
						'Content-Type' => 'application/json',
					),
					'body'    => json_encode( $this->logFileData ),
					'method'  => 'POST',
					'timeout' => 45,
				);
				$response        = wp_remote_post( $post_url, $args );
				$response_status = wp_remote_retrieve_response_code( $response );
				$response        = wp_remote_retrieve_body( $response );
				$test_response = $response;
				$test_response = json_decode( $test_response );
				if ( ! empty( $response ) && $test_response->status == 'success' ) {
					unlink( BLOYAL_UPLOAD_DIR_BASEPATH . '/bloyal_temp_log_snippet.txt' );
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
				self::$snippet_log_enable = bloyal_snippet_log_settings();
				if( 'true' === self::$snippet_log_enable) {
					$read_snippet_log_file = BLOYAL_UPLOAD_DIR_BASEPATH . '/bloyal_temp_log_snippet.txt';
					$logTempFile = fopen( $read_snippet_log_file, 'a' );
					fwrite( $logTempFile, file_get_contents( self::$logFileName ) );
					fclose( $logTempFile );
					unlink( self::$logFileName );
					$handle            = fopen( BLOYAL_UPLOAD_DIR_BASEPATH . '/bloyal_temp_log_snippet.txt', 'r' );
					$this->logFileData = array();
					$data              = $currentLogType = $fileName = $dateTime = $functionName = '';
					while ( $line = fgets( $handle, 1000 ) ) {
						$line  = trim( $line );
						$data .= $line;
						if ( strpos( $line, 'Log:' ) === 0 ) {
							foreach ( self::$logtype as $key => $type ) {
								if ( strpos( $line, $type ) !== false ) {
									$currentLogType = $type;
								}
							}
						}
						if ( strpos( $line, 'File:' ) === 0 ) {
							$fileName = str_replace( 'File:', '', $line );
						}
						if ( strpos( $line, 'Date time:' ) === 0 ) {
							$dateTime = str_replace( 'Date time:', '', $line );
						}
						if ( strpos( $line, 'Function:' ) === 0 ) {
							$functionName = str_replace( 'Function:', '', $line );
						}
						if ( strpos( $line, self::$logDelimeter ) !== false ) {
							$message_details     = array(
								str_replace( self::$logDelimeter, '', $data ),
							);
							$this->logFileData[] =
							array(
								'ContextExternalId' => $fileName,
								'EntityTypeName'    => "Woocommerce Plugin {$currentLogType} Logs For function: {$functionName}.",
								'MessageFormat'     => 'Text',
								'MessageDetail'     => $message_details,
								'EntityValue'       => array(),
								'EventType'         => $currentLogType,
								'Duration'          => '',
								'Message'           => "bLoyal Woocommerce Connector Logs for Client: {$this->domainName}",
								'Created'           => $dateTime,
								'Stack'             => '',
							);
							$data                = $currentLogType = $fileName = $dateTime = $functionName = '';
						}
					}
				}
			} catch ( Exception $e ) {
				self::write_custom_log( $e->getMessage(), 3 );
			}
		}
	}
}
