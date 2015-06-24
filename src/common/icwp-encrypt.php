<?php

if ( !class_exists( 'ICWP_APP_Encypt_V1', false ) ):

	class ICWP_APP_Encrypt_V1 {

		/**
		 * @var ICWP_APP_Encrypt_V1
		 */
		protected static $oInstance = NULL;

		/**
		 * @return ICWP_APP_Encrypt_V1
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}

		private function __construct() {}

		/**
		 * @return bool
		 */
		public function getSupportsOpenSslSign() {
			return function_exists( 'base64_decode' )
				   && function_exists( 'openssl_sign' )
				   && function_exists( 'openssl_verify' )
				   && defined( 'OPENSSL_ALGO_SHA1' );
		}

		/**
		 * @return bool
		 */
		public function getSupportsOpenSslDataEncryption() {
			return function_exists( 'openssl_seal' ) && function_exists( 'openssl_open' );
		}

		/**
		 * @param mixed $mDataToEncrypt
		 * @param string $sPublicKey
		 * @return stdClass					3 members: result, encrypted, password
		 */
		public function encryptDataPublicKey( $mDataToEncrypt, $sPublicKey ) {

			$oEncryptResponse = $this->getStandardEncryptResponse();

			if ( empty( $mDataToEncrypt ) ) {
				$oEncryptResponse->success = false;
				$oEncryptResponse->message = 'Data to encrypt was empty';
				return $oEncryptResponse;
			}
			else if ( !$this->getSupportsOpenSslDataEncryption() ) {
				$oEncryptResponse->success = false;
				$oEncryptResponse->message = 'Does not support OpenSSL data encryption';
			}

			// If at this stage we're not 'success' we return it.
			if ( !$oEncryptResponse->success ) {
				return $oEncryptResponse;
			}

			if ( !is_string( $mDataToEncrypt ) ) {
				$mDataToEncrypt = serialize( $mDataToEncrypt );
				$oEncryptResponse->serialized = true;
			}
			else {
				$oEncryptResponse->serialized = false;
			}

			$aPasswordKeys = array();
			$nResult = openssl_seal( $mDataToEncrypt, $sEncryptedData, $aPasswordKeys, array( $sPublicKey ) );

			$oEncryptResponse->success = true;
			$oEncryptResponse->result = $nResult;
			$oEncryptResponse->encrypted_data = $sEncryptedData;
			$oEncryptResponse->encrypted_password = $aPasswordKeys[0];

			return $oEncryptResponse;
		}

		/**
		 * @param string $sVerificationCode
		 * @param string $sSignature
		 * @param string $sPublicKey
		 *
		 * @return int					1: Success; 0: Failure; -1: Error; -2: Not supported
		 */
		public function verifySslSignature( $sVerificationCode, $sSignature, $sPublicKey ) {
			$nResult = -2;
			if ( $this->getSupportsOpenSslSign() ) {
				$nResult = openssl_verify( $sVerificationCode, $sSignature, $sPublicKey );
			}
			return $nResult;
		}
		/**
		 * @return stdClass
		 */
		protected function getStandardEncryptResponse() {
			$oEncryptResponse = new stdClass();
			$oEncryptResponse->success = true;
			$oEncryptResponse->result = null;
			$oEncryptResponse->message = '';
			$oEncryptResponse->serialized = false;
			$oEncryptResponse->encrypted_data = null;
			$oEncryptResponse->encrypted_password = null;
			return $oEncryptResponse;
		}
	}
endif;

if ( !class_exists( 'ICWP_APP_Encrypt', false ) ) :

	class ICWP_APP_Encrypt extends ICWP_APP_Encrypt_V1 {
		/**
		 * @return ICWP_APP_Encrypt
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}
	}
endif;