<?php

if ( class_exists( 'ICWP_APP_Api_Internal_Collect_Base', false ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ) . ICWP_DS . 'base.php' );

class ICWP_APP_Api_Internal_Collect_Base extends ICWP_APP_Api_Internal_Base {

	/**
	 * @param bool $bFull
	 * @return array
	 */
	protected function collectCapabilities( $bFull = false ) {
		require_once( dirname( __FILE__ ) . ICWP_DS . 'capabilites.php' );
		$oCollector = new ICWP_APP_Api_Internal_Collect_Capabilities();
		$oCollector->setRequestParams( $this->getRequestParams() );
		return $oCollector->collect( $bFull );
	}

	/**
	 * @param string $sContext
	 * @return mixed
	 */
	protected function getAutoUpdates( $sContext = 'plugins' ) {
		return ICWP_Plugin::GetAutoUpdatesSystem()->getAutoUpdates( $sContext );
	}
}