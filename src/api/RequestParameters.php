<?php

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
	 * @param array $aGetParams
	 * @param array $aPostParams
	 */
	public function __construct( $aGetParams, $aPostParams ) {
		$aGetParams = empty( $aGetParams ) ? array() : maybe_unserialize( base64_decode( $aGetParams ) );
		$aPostParams = empty( $aPostParams ) ? array() : maybe_unserialize( base64_decode( $aPostParams ) );
		$this->aRequestParams = array_merge( $_GET, $_POST, $aGetParams, $aPostParams );
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
	 * @return int
	 */
	public function getApiHookPriority() {
		$nHookPriority = $this->getParam( 'api_priority' );
		if ( empty( $nHookPriority ) || !is_numeric( $nHookPriority )) {
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
	public function getIsApiCall() {
		return ( ( $this->getParam( 'worpit_link', 0 ) == 1 ) || ( $this->getParam( 'worpit_api', 0 ) == 1 ) );
	}

	/**
	 * @return bool
	 */
	public function getIsApiCall_Action() {
		return $this->getIsApiCall() && ( $this->getParam( 'worpit_api', 0 ) == 1 );
	}

	/**
	 * @return bool
	 */
	public function getIsApiCall_LinkSite() {
		return $this->getIsApiCall() && ( $this->getParam( 'worpit_link', 0 ) == 1 );
	}

	/**
	 * @param string $sKey
	 * @param mixed $mDefault
	 * @return mixed
	 */
	public function getParam( $sKey, $mDefault = '' ) {
		return isset( $this->aRequestParams[ $sKey ] ) ? $this->aRequestParams[ $sKey ] : $mDefault;
	}
}