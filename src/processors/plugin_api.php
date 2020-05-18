<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

/**
 * Class ICWP_APP_Processor_Plugin_Api
 */
abstract class ICWP_APP_Processor_Plugin_Api extends ICWP_APP_Processor_BaseApp {

	/**
	 * @var LegacyApi\ApiResponse
	 */
	protected static $oActionResponse;

	/**
	 * @var string
	 */
	protected $sLoggedInUser;

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function run() {
		$oActionResponse = self::getStandardResponse();
		$this->preActionVerify();
		if ( $oActionResponse->success ) {
			$this->preActionEnvironmentSetup();
			$this->processAction();
		}
		$this->postProcessAction();
		return $oActionResponse;
	}

	protected function preActionVerify() {
		/** @var \ICWP_APP_FeatureHandler_Plugin $oMod */
		$oMod = $this->getFeatureOptions();

		$oResponse = $this->getStandardResponse();
		$oResponse->channel = $this->getApiChannel();

		$this->preApiCheck();

		if ( !$oResponse->success ) {
			if ( !$this->attemptSiteReassign()->success ) {
				return;
			}
		}

		$this->handshake();

		if ( !$oResponse->success ) {
			if ( $oResponse->code == 9991 ) {
				$oMod->setCanHandshake(); //recheck ability to handshake
			}
		}
	}

	protected function postProcessAction() {
		$oR = $this->getStandardResponse();
		$aData = $oR->data;
		$aData[ 'verification_code' ] = $this->getRequestParams()->getStringParam( 'verification_code', 'no code' );
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	abstract protected function processAction();

	/**
	 * @return string
	 */
	protected function getApiChannel() {
		/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
		$oFO = $this->getFeatureOptions();
		$sApiChannel = $this->getRequestParams()->getApiChannel();
		if ( !in_array( $sApiChannel, $oFO->getPermittedApiChannels() ) ) {
			$sApiChannel = 'index';
		}
		return $sApiChannel;
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	protected function preApiCheck() {
		/** @var ICWP_APP_FeatureHandler_Plugin $oMod */
		$oMod = $this->getFeatureOptions();
		$oReqParams = $this->getRequestParams();
		$oResponse = $this->getStandardResponse();

		if ( !$oMod->getIsSiteLinked() ) {
			$sErrorMessage = 'NotAssigned';
			return $this->setErrorResponse(
				$sErrorMessage,
				9999
			);
		}

		$sRequestKey = $oReqParams->getAuthKey();
		if ( empty( $sRequestKey ) ) {
			$sErrorMessage = 'EmptyRequestKey';
			return $this->setErrorResponse(
				$sErrorMessage,
				9995
			);
		}
		$sKey = $oMod->getPluginAuthKey();
		if ( $sRequestKey != $sKey ) {
			$sErrorMessage = 'InvalidKey';
			return $this->setErrorResponse(
				$sErrorMessage,
				9998
			);
		}

		$sRequestPin = $oReqParams->getPin();
		if ( empty( $sRequestPin ) ) {
			$sErrorMessage = 'EmptyRequestPin';
			return $this->setErrorResponse(
				$sErrorMessage,
				9994
			);
		}
		$sPin = $oMod->getPluginPin();
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
	 * @return LegacyApi\ApiResponse
	 */
	protected function attemptSiteReassign() {
		/** @var ICWP_APP_FeatureHandler_Plugin $oMod */
		$oMod = $this->getFeatureOptions();
		$oReqParams = $this->getRequestParams();

		$sChannel = $oReqParams->getApiChannel();
		if ( empty( $sChannel ) || !in_array( $sChannel, [ 'auth', 'internal', 'retrieve' ] ) ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Site action method is neither "retrieve" nor "internal".' ),
				9806
			);
		}

		// We first verify fully if we CAN handshake
		if ( !$oMod->getCanHandshake( true ) ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Site cannot handshake' ),
				9801
			);
		}

		$this->handshake();
		$oResponse = $this->getStandardResponse();

		if ( !$oResponse->success ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Handshake verify failed' ),
				9802
			);
		}

		$sRequestedAcc = $oReqParams->getAccountId();
		if ( empty( $sRequestedAcc ) || !is_email( $sRequestedAcc ) ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Request account empty or invalid' ),
				9803
			);
		}

		$sRequestedKey = $oReqParams->getAuthKey();
		if ( empty( $sRequestedKey ) || strlen( $sRequestedKey ) != 24 ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'Auth Key not of the correct format' ),
				9804
			);
		}

		$sRequestPin = $oReqParams->getPin();
		if ( empty( $sRequestPin ) ) {
			return $this->setErrorResponse(
				sprintf( 'Attempting Site Reassign Failed: %s.', 'PIN empty' ),
				9805
			);
		}

		$oMod->setOpt( 'key', $sRequestedKey );
		$oMod->setAssignedAccount( $sRequestedAcc );
		$oMod->setPluginPin( $sRequestPin );
		$oMod->savePluginOptions();

		return $this->setSuccessResponse(
			'Attempting Site Reassign Succeeded.',
			9800
		);
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	protected function handshake() {
		/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
		$oFO = $this->getFeatureOptions();
		$oReqParams = $this->getRequestParams();
		$oResponse = $this->getStandardResponse();

		if ( !$oFO->getCanHandshake() ) {
			$oResponse->handshake = 'unsupported';
			return $oResponse;
		}
		$oResponse->handshake = 'failed';

		$sPin = $oReqParams->getPin();
		$sPackageName = $oReqParams->getPackageName();
		$sVerificationCode = $oReqParams->getVerificationCode();
		if ( empty( $sVerificationCode ) || empty( $sPackageName ) || empty( $sPin ) ) {
			return $this->setErrorResponse(
				'Either the Verification Code, Package Name, or PIN were empty. Could not Handshake.',
				9990
			);
		}

		$oEncryptProcessor = $this->loadEncryptProcessor();
		if ( $oEncryptProcessor->getSupportsOpenSslSign() ) {
			$sSignature = $oReqParams->getOpenSslSignature();
			$sPublicKey = $oFO->getIcwpPublicKey();
			if ( !empty( $sSignature ) && !empty( $sPublicKey ) ) {
				$nSslSuccess = $oEncryptProcessor->verifySslSignature( $sVerificationCode, $sSignature, $sPublicKey );
				$oResponse->openssl_verify = $nSslSuccess;
				if ( $nSslSuccess === 1 ) {
					$oResponse->handshake = 'openssl';
					return $this->setSuccessResponse(); // just to be sure we proceed thereafter
				}
			}
		}

		$sHandshakeVerifyBaseUrl = $oFO->getAppUrl( 'handshake_verify_url' );
		// We can do this because we've assumed at this point we've validated the communication with iControlWP
		$sHandshakeVerifyUrl = sprintf(
			'%s/%s/%s/%s',
			rtrim( $sHandshakeVerifyBaseUrl, '/' ),
			$sVerificationCode,
			$sPackageName,
			$sPin
		);

		$sResponse = $this->loadFS()->getUrlContent( $sHandshakeVerifyUrl );
		if ( empty( $sResponse ) ) {
			return $this->setErrorResponse(
				sprintf( 'Package Handshaking Failed against URL "%s" with an empty response.', $sHandshakeVerifyUrl ),
				9991
			);
		}

		$oJsonResponse = json_decode( trim( $sResponse ) );
		if ( !is_object( $oJsonResponse ) || !isset( $oJsonResponse->success ) || $oJsonResponse->success !== true ) {
			return $this->setErrorResponse(
				sprintf( 'Package Handshaking Failed against URL "%s" with response: "%s".', $sHandshakeVerifyUrl, print_r( $oJsonResponse, true ) ),
				9992
			);
		}

		$oResponse->handshake = 'url';
		return $this->setSuccessResponse(); //just to be sure we proceed thereafter
	}

	protected function preActionEnvironmentSetup() {
		$this->loadWP()->doBustCache();
		@set_time_limit( $this->getRequestParams()->getTimeout() );
	}

	/**
	 * @return bool
	 */
	protected function setWpEngineAuth() {
		if ( @getenv( 'IS_WPE' ) == '1' && class_exists( 'WpeCommon', false ) && $this->isLoggedInUser() ) {
			$oWpEngineCommon = WpeCommon::instance();
			$oWpEngineCommon->set_wpe_auth_cookie();
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	protected function setAuthorizedUser() {

		if ( !$this->isLoggedInUser() ) {
			$oWpUser = $this->loadWpUsers();
			$oReqParams = $this->getRequestParams();
			$sWpUser = $oReqParams->getStringParam( 'wpadmin_user' );
			if ( empty( $sWpUser ) ) {

				if ( version_compare( $this->loadWP()->getWordpressVersion(), '3.1', '>=' ) ) {
					$aUserRecords = get_users( [
						'role'    => 'administrator',
						'number'  => 1,
						'orderby' => 'ID'
					] );
					if ( is_array( $aUserRecords ) && count( $aUserRecords ) ) {
						$oUser = $aUserRecords[ 0 ];
					}
				}
				else {
					$oUser = $oWpUser->getUserById( 1 );
				}
				$sWpUser = ( !empty( $oUser ) && is_a( $oUser, 'WP_User' ) ) ? $oUser->get( 'user_login' ) : 'admin';
			}

			if ( $oWpUser->setUserLoggedIn( $sWpUser, $oReqParams->isSilentLogin() ) ) {
				$this->setLoggedInUser( $sWpUser );
			}
		}
		return $this->isLoggedInUser();
	}

	/**
	 * Used by Execute and Retrieve
	 * @param string $sInstallerFileToInclude
	 * @return LegacyApi\ApiResponse
	 */
	protected function runInstaller( $sInstallerFileToInclude ) {
		$oFs = $this->loadFS();

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
		$aResponseData = isset( $aInstallerResponse[ 'data' ] ) ? $aInstallerResponse[ 'data' ] : [];

		if ( isset( $aInstallerResponse[ 'success' ] ) && $aInstallerResponse[ 'success' ] ) {
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
	 * @param int    $nErrorCode
	 * @param mixed  $mErrorData
	 * @return LegacyApi\ApiResponse
	 */
	protected function setErrorResponse( $sErrorMessage = '', $nErrorCode = -1, $mErrorData = '' ) {
		return $this->getStandardResponse()
					->setFailed()
					->setErrorMessage( $sErrorMessage )
					->setCode( $nErrorCode )
					->setData( $mErrorData );
	}

	/**
	 * @param string $sMessage
	 * @param int    $nSuccessCode
	 * @param mixed  $mData
	 * @return LegacyApi\ApiResponse
	 */
	protected function setSuccessResponse( $sMessage = '', $nSuccessCode = 0, $mData = null ) {
		return $this->getStandardResponse()
					->setSuccess( true )
					->setMessage( $sMessage )
					->setCode( $nSuccessCode )
					->setData( is_null( $mData ) ? [ 'success' => 1 ] : $mData );
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	static public function getStandardResponse() {
		if ( is_null( self::$oActionResponse ) ) {
			self::$oActionResponse = new LegacyApi\ApiResponse();
		}
		return self::$oActionResponse;
	}

	/**
	 * @param string $sUser
	 * @return $this
	 */
	protected function setLoggedInUser( $sUser ) {
		$this->sLoggedInUser = $sUser;
		return $this;
	}

	/**
	 * @return string
	 */
	protected function getLoggedInUser() {
		$sLoggedInUser = $this->sLoggedInUser;
		if ( empty( $sLoggedInUser ) ) {
			$oWpUser = $this->loadWpUsers();
			if ( $oWpUser->isUserLoggedIn() && $oWpUser->isUserAdmin() ) {
				$sLoggedInUser = $oWpUser->getCurrentWpUser()->get( 'user_login' );
				$this->setLoggedInUser( $sLoggedInUser );
			}
		}
		return $this->sLoggedInUser;
	}

	/**
	 * @return bool
	 */
	protected function isLoggedInUser() {
		$sLoggedInUser = $this->getLoggedInUser();
		return !empty( $sLoggedInUser );
	}
}