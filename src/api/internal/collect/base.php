<?php

class ICWP_APP_Api_Internal_Collect_Base extends ICWP_APP_Api_Internal_Base {

	/**
	 * @var ICWP_APP_Api_Internal_Collect_Base[]
	 */
	private $aCollectors;

	/**
	 * @return ICWP_APP_Api_Internal_Collect_Base|ICWP_APP_Api_Internal_Collect_Capabilities
	 */
	protected function getCollector_Capabilities() {
		$sKey = 'capabilities';
		if ( !isset( $this->aCollectors[ $sKey ] ) ) {
			$oCollector = new ICWP_APP_Api_Internal_Collect_Capabilities();
			$this->aCollectors[ $sKey ] = $oCollector->setRequestParams( $this->getRequestParams() );
		}
		return $this->aCollectors[ $sKey ];
	}

	/**
	 * @return ICWP_APP_Api_Internal_Collect_Base|ICWP_APP_Api_Internal_Collect_Paths
	 */
	protected function getCollector_Paths() {
		$sKey = 'paths';
		if ( !isset( $this->aCollectors[ $sKey ] ) ) {
			$oCollector = new ICWP_APP_Api_Internal_Collect_Paths();
			$this->aCollectors[ $sKey ] = $oCollector->setRequestParams( $this->getRequestParams() );
		}
		return $this->aCollectors[ $sKey ];
	}

	/**
	 * @return ICWP_APP_Api_Internal_Collect_Base|ICWP_APP_Api_Internal_Collect_Wordpress
	 */
	protected function getCollector_WordPressInfo() {
		$sKey = 'wordpress-info';
		if ( !isset( $this->aCollectors[ $sKey ] ) ) {
			$oCollector = new ICWP_APP_Api_Internal_Collect_Wordpress();
			$this->aCollectors[ $sKey ] = $oCollector->setRequestParams( $this->getRequestParams() );
		}
		return $this->aCollectors[ $sKey ];
	}

	/**
	 * @param string $sContext
	 * @return mixed
	 */
	protected function getAutoUpdates( $sContext = 'plugins' ) {
		return ICWP_Plugin::GetAutoUpdatesSystem()->getAutoUpdates( $sContext );
	}
}