<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api
	 */
	abstract class ICWP_APP_Processor_Plugin_Api extends ICWP_APP_Processor_Base {

		/**
		 * @var stdClass
		 */
		protected static $oActionResponse;

		/**
		 * @var ICWP_APP_FeatureHandler_Plugin
		 */
		protected $oFeatureOptions;

		/**
		 * @return stdClass
		 */
		public function run() {
			$oActionExecutionResponse = $this->preActionVerify();
			if ( !$oActionExecutionResponse->success ) {
				$this->preActionEnvironmentSetup();
				$oActionExecutionResponse = $this->processAction();
			}
			return $oActionExecutionResponse;
		}

		/**
		 * @return stdClass
		 */
		protected function preActionVerify() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oResponse = $this->getStandardResponse();
			$oResponse->channel = $this->getApiChannel();

			$this->preApiCheck();
			if ( !$oResponse->success ) {
				if ( !$this->attemptSiteReassign()->success ) {
					return $oResponse;
				}
			}

			$this->handshake();
			if ( !$oResponse->success ) {
				if ( $oResponse->code == 9991 ) {
					$oFO->setCanHandshake(); //recheck ability to handshake
				}
				return $oResponse;
			}

			return $oResponse;
		}

		/**
		 * @return stdClass
		 */
		abstract protected function processAction();

		/**
		 * @return string
		 */
		protected function getApiChannel() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$sApiChannel = $oFO->fetchIcwpRequestParam( 'm', 'index' );
			if ( !in_array( $sApiChannel, $oFO->getPermittedApiChannels() ) ) {
				$sApiChannel = 'index';
			}
			return $sApiChannel;
		}

		/**
		 * @return stdClass
		 */
		protected function preApiCheck() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oResponse = $this->getStandardResponse();

			if ( !$oFO->getIsSiteLinked() ) {
				$sErrorMessage = 'NotAssigned';
				return $this->setErrorResponse(
					$sErrorMessage,
					9999
				);
			}

			$sKey = $oFO->getPluginAuthKey();
			$sRequestKey = trim( $oFO->fetchIcwpRequestParam( 'key', false ) );
			if ( empty( $sRequestKey ) ) {
				$sErrorMessage = 'EmptyRequestKey';
				return $this->setErrorResponse(
					$sErrorMessage,
					9995
				);
			}
			if ( $sRequestKey != $sKey ) {
				$sErrorMessage = 'InvalidKey';
				return $this->setErrorResponse(
					$sErrorMessage,
					9998
				);
			}

			$sPin = $oFO->getPluginPin();
			$sRequestPin = trim( $oFO->fetchIcwpRequestParam( 'pin', '' ) );
			if ( empty( $sRequestPin ) ) {
				$sErrorMessage = 'EmptyRequestPin';
				return $this->setErrorResponse(
					$sErrorMessage,
					9994
				);
			}
			if ( md5( $sRequestPin ) != $sPin ) {
				$sErrorMessage = 'InvalidPin';
				return $this->setErrorResponse(
					$sErrorMessage,
					9997
				);
			}

			return $oResponse;
		}

		/**
		 * Attempts to relink/reassign a site upon API failure, with certain pre-conditions
		 *
		 * 1) The channel is "retrieve"
		 * 2) The site CAN Handshake (it will check this)
		 * 3) The handshake is verified for this package
		 *
		 * @return stdClass
		 */
		protected function attemptSiteReassign() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oResponse = $this->getStandardResponse();

			if ( !isset( $oResponse->channel ) || !in_array( $oResponse->channel, array( 'internal', 'retrieve' ) ) ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Site action method is neither "retrieve" nor "internal".' ),
					9806
				);
			}

			// We first verify fully if we CAN handshake
			if ( !$oFO->getCanHandshake( true ) ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Site cannot handshake' ),
					9801
				);
			}

			$oResponse = $this->handshake();
			if ( !$oResponse->success ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Handshake verify failed' ),
					9802
				);
			}

			$sRequestedAcc = urldecode( $oFO->fetchIcwpRequestParam( 'accname' ) );
			if ( empty( $sRequestedAcc ) || !is_email( $sRequestedAcc ) ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Request account empty or invalid' ),
					9803
				);
			}

			$sRequestedKey = $oFO->fetchIcwpRequestParam( 'key', '' );
			if ( empty( $sRequestedKey ) || strlen( $sRequestedKey ) != 24 ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'Auth Key not of the correct format' ),
					9804
				);
			}

			$sRequestPin = $oFO->fetchIcwpRequestParam( 'pin', '' );
			if ( empty( $sRequestPin ) ) {
				return $this->setErrorResponse(
					sprintf( 'Attempting Site Reassign Failed: %s.', 'PIN empty' ),
					9805
				);
			}

			$oFO->setOpt( 'key', $sRequestedKey );
			$oFO->setPluginAssigned( $sRequestedAcc );
			$oFO->setPluginPin( $sRequestPin );
			$oFO->savePluginOptions();

			return $this->setSuccessResponse(
				'Attempting Site Reassign Succeeded.',
				9800
			);
		}

		/**
		 * @return stdClass
		 */
		protected function handshake() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oDp = $this->loadDataProcessor();
			$oResponse = $this->getStandardResponse();

			if( !$oFO->getCanHandshake() ) {
				$oResponse->handshake = 'unsupported';
				return $oResponse;
			}
			$oResponse->handshake = 'failed';

			$oEncryptProcessor = $this->loadEncryptProcessor();

			$sVerificationCode = $oFO->fetchIcwpRequestParam( 'verification_code', false );
			if ( $oEncryptProcessor->getSupportsOpenSslSign() ) {
				$sSignature = base64_decode( $oFO->fetchIcwpRequestParam( 'opensig', '' ) );
				$sPublicKey = $oFO->getIcwpPublicKey();
				if ( !empty( $sSignature ) && !empty( $sPublicKey ) ) {
					$oResponse->openssl_verify = $oEncryptProcessor->verifySslSignature( $sVerificationCode, $sSignature, $sPublicKey );
					if ( $oResponse->openssl_verify === 1 ) {
						$oResponse->handshake = 'openssl';
						return $this->setSuccessResponse(); // just to be sure we proceed thereafter
					}
				}
			}

			$sPackageName = $oFO->fetchIcwpRequestParam( 'package_name', false );
			$sPin = $oFO->fetchIcwpRequestParam( 'pin', false );

			if ( empty( $sVerificationCode ) || empty( $sPackageName ) || empty( $sPin ) ) {
				return $this->setErrorResponse(
					'Either the Verification Code, Package Name, or PIN were empty. Could not Handshake.',
					9990
				);
			}

			$sHandshakeVerifyBaseUrl = $this->getOption( 'handshake_verify_url' );
			// We can do this because we've assumed at this point we've validated the communication with iControlWP
			$sHandshakeVerifyUrl = sprintf(
				'%s/%s/%s/%s',
				rtrim( $sHandshakeVerifyBaseUrl, '/' ),
				$sVerificationCode,
				$sPackageName,
				$sPin
			);

			$oFs = $this->loadFileSystemProcessor();
			$sResponse = $oFs->getUrlContent( $sHandshakeVerifyUrl );
			if ( empty( $sResponse ) ) {
				return $this->setErrorResponse(
					sprintf( 'Package Handshaking Failed against URL "%s" with an empty response.', $sHandshakeVerifyUrl ),
					9991
				);
			}

			$oJsonResponse = $oDp->doJsonDecode( trim( $sResponse ) );
			if ( !is_object( $oJsonResponse ) || !isset( $oJsonResponse->success ) || $oJsonResponse->success !== true ) {
				return $this->setErrorResponse(
					sprintf( 'Package Handshaking Failed against URL "%s" with response: "%s".', $sHandshakeVerifyUrl, print_r( $oJsonResponse,true ) ),
					9992
				);
			}

			$oResponse->handshake = 'url';
			return $this->setSuccessResponse(); //just to be sure we proceed thereafter
		}

		/**
		 */
		protected function preActionEnvironmentSetup() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$this->loadWpFunctionsProcessor()->doBustCache();
			@set_time_limit( $oFO->fetchIcwpRequestParam( 'timeout', 60 ) );
			$this->setWpEngineAuth();
		}

		/**
		 */
		protected function setWpEngineAuth() {
			if ( @getenv( 'IS_WPE' ) == '1' && class_exists( 'WpeCommon', false ) && $this->setAuthorizedUser() ) {
				$oWpEngineCommon = WpeCommon::instance();
				$oWpEngineCommon->set_wpe_auth_cookie();
			}
		}

		/**
		 * @return bool
		 */
		protected function setAuthorizedUser() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oWp = $this->loadWpFunctionsProcessor();
			$sWpUser = $oFO->fetchIcwpRequestParam( 'wpadmin_user' );
			if ( empty( $sWpUser ) ) {

				if ( version_compare( $oWp->getWordpressVersion(), '3.1', '>=' ) ) {
					$aUserRecords = get_users( 'role=administrator' );
					if ( is_array( $aUserRecords ) && count( $aUserRecords ) ) {
						$oUser = $aUserRecords[0];
					}
				}
				else {
					$oUser = $oWp->getUserById( 1 );
				}
				$sWpUser = ( !empty( $oUser ) && is_a( $oUser, 'WP_User' ) ) ? $oUser->get( 'user_login' ) : '';
			}

			return $oWp->setUserLoggedIn( empty( $sWpUser ) ? 'admin' : $sWpUser );
		}

		/**
		 * Used by Execute and Retrieve
		 * @param string $sInstallerFileToInclude
		 * @return stdClass
		 */
		protected function runInstaller( $sInstallerFileToInclude ) {
			$oFs = $this->loadFileSystemProcessor();

			$bIncludeSuccess = include_once( $sInstallerFileToInclude );
			$oFs->deleteFile( $sInstallerFileToInclude );

			if ( !$bIncludeSuccess ) {
				return $this->setErrorResponse(
					'PHP failed to include the Installer file for execution.'
				);
			}

			if ( !class_exists( 'Worpit_Package_Installer', false ) ) {
				$sErrorMessage = sprintf( 'Worpit_Package_Installer class does not exist after including file: "%s".', $sInstallerFileToInclude );
				return $this->setErrorResponse(
					$sErrorMessage,
					-1 //TODO: Set a code
				);
			}

			$oInstaller = new Worpit_Package_Installer();
			$aInstallerResponse = $oInstaller->run();

			$sMessage = !empty( $aInstallerResponse[ 'message' ] ) ? $aInstallerResponse[ 'message' ] : 'No message';
			$aResponseData = isset( $aInstallerResponse[ 'data' ] ) ? $aInstallerResponse[ 'data' ] : array();

			if ( isset( $aInstallerResponse['success'] ) && $aInstallerResponse['success'] ) {
				return $this->setSuccessResponse(
					sprintf( 'Package Execution SUCCEEDED with message: "%s".', $sMessage ),
					0,
					$aResponseData
				);
			}
			else {
				return $this->setErrorResponse(
					sprintf( 'Package Execution FAILED with error message: "%s"', $sMessage ),
					-1, //TODO: Set a code
					$aResponseData
				);
			}
		}

		/**
		 * @param string $sErrorMessage
		 * @param int $nErrorCode
		 * @param mixed $mErrorData
		 * @return stdClass
		 */
		protected function setErrorResponse( $sErrorMessage = '', $nErrorCode = -1, $mErrorData = '' ) {
			$oResponse = $this->getStandardResponse();
			$oResponse->success = false;
			$oResponse->error_message .= ' '.$sErrorMessage;
			$oResponse->code = $nErrorCode;
			$oResponse->data = $mErrorData;
			return $oResponse;
		}

		/**
		 * @param string $sMessage
		 * @param int $nSuccessCode
		 * @param mixed $mData
		 * @return stdClass
		 */
		protected function setSuccessResponse( $sMessage = '', $nSuccessCode = 0, $mData = null ) {
			$oResponse = $this->getStandardResponse();
			$oResponse->success = true;
			$oResponse->message = $sMessage;
			$oResponse->code = $nSuccessCode;
			$oResponse->data = is_null( $mData ) ? array( 'success' => 1 ) : $mData;
			return $oResponse;
		}

		/**
		 * @return stdClass
		 */
		static public function getStandardResponse() {
			if ( is_null( self::$oActionResponse ) ) {
				$oResponse = new stdClass();
				$oResponse->error_message = '';
				$oResponse->message = '';
				$oResponse->success = true;
				$oResponse->code = 0;
				$oResponse->data = null;
				$oResponse->channel = '';
				$oResponse->die = false;
				$oResponse->handshake = 'none';
				self::$oActionResponse = $oResponse;
			}
			return self::$oActionResponse;
		}
	}

endif;