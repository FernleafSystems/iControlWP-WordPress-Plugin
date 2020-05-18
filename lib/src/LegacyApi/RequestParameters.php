<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

/**
 * Class RequestParameters
 */
class RequestParameters {

	/**
	 * @var array
	 */
	protected $aRequestParams;

	/**
	 * RequestParameters constructor.
	 *
	 * @param string $aGetParams
	 * @param string $aPostParams
	 */
	public function __construct( $aGetParams, $aPostParams ) {
		$aGetParams = empty( $aGetParams ) ? [] : maybe_unserialize( base64_decode( $aGetParams ) );
		$aPostParams = empty( $aPostParams ) ? [] : maybe_unserialize( base64_decode( $aPostParams ) );
		$this->aRequestParams = array_merge( $_GET, $_POST, $aGetParams, $aPostParams );
	}

	/**
	 * @return array
	 */
	public function getActionParams() {
		$sSerialized = $this->getParam( 'action_params' );
		return empty( $sSerialized ) ? [] : unserialize( $sSerialized );
	}

	/**
	 * @return string
	 */
	public function getApiHook() {
		$sApiHook = $this->getParam( 'api_priority' );
		if ( empty( $sApiHook ) || !is_string( $sApiHook ) ) {
			$sApiHook = is_admin() ? 'admin_init' : 'wp_loaded';
			if ( class_exists( 'WooDojo_Maintenance_Mode', false ) || class_exists( 'ITSEC_Core', false ) ) {
				$sApiHook = 'init';
			}
		}
		return $sApiHook;
	}

	/**
	 * @return string email
	 */
	public function getAccountId() {
		return urldecode( $this->getStringParam( 'accname' ) );
	}

	/**
	 * @return string
	 */
	public function getApiAction() {
		return $this->getStringParam( 'action' );
	}

	/**
	 * @return string
	 */
	public function getApiChannel() {
		return $this->getStringParam( 'm', 'index' );
	}

	/**
	 * @return string
	 */
	public function getAuthKey() {
		return $this->getStringParam( 'key' );
	}

	/**
	 * @return string
	 */
	public function getOpenSslSignature() {
		return base64_decode( $this->getStringParam( 'opensig' ) );
	}

	/**
	 * @return string
	 */
	public function getPackageName() {
		return $this->getStringParam( 'package_name' );
	}

	/**
	 * @return string
	 */
	public function getPin() {
		return $this->getStringParam( 'pin' );
	}

	/**
	 * @return int
	 */
	public function getTimeout() {
		return (int)$this->getParam( 'timeout', 60 );
	}

	/**
	 * @return string
	 */
	public function getVerificationCode() {
		return $this->getStringParam( 'verification_code' );
	}

	/**
	 * @return int
	 */
	public function getApiHookPriority() {
		$nHookPriority = $this->getParam( 'api_priority' );
		if ( empty( $nHookPriority ) || !is_numeric( $nHookPriority ) ) {
			$nHookPriority = is_admin() ? 101 : 1;
			if ( class_exists( 'ITSEC_Core', false ) ) {
				$nHookPriority = 100;
			}
		}
		return (int)$nHookPriority;
	}

	/**
	 * @return bool
	 */
	public function isSilentLogin() {
		return ( (int)$this->getParam( 'silent_login' ) > 0 );
	}

	/**
	 * @return bool
	 */
	public function getIsApiCall() {
		return $this->getIsApiCall_Action() || $this->getIsApiCall_LinkSite();
	}

	/**
	 * @return bool
	 */
	public function getIsApiCall_Action() {
		return ( $this->getParam( 'worpit_api', 0 ) || $this->getParam( 'icwpapi', 0 ) );
	}

	/**
	 * @return bool
	 */
	public function getIsApiCall_LinkSite() {
		return $this->getParam( 'worpit_link', 0 );
	}

	/**
	 * @param string $sKey
	 * @param string $mDefault
	 * @return string
	 */
	public function getStringParam( $sKey, $mDefault = '' ) {
		$sVal = $this->getParam( $sKey, $mDefault );
		return ( !empty( $sVal ) && is_string( $sVal ) ) ? trim( $sVal ) : $mDefault;
	}

	/**
	 * @param string $sKey
	 * @param mixed  $mDefault
	 * @return mixed
	 */
	public function getParam( $sKey, $mDefault = '' ) {
		return isset( $this->aRequestParams[ $sKey ] ) ? $this->aRequestParams[ $sKey ] : $mDefault;
	}
}