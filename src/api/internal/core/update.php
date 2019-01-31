<?php

if ( class_exists( 'ICWP_APP_Api_Internal_Core_Update', false ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

class ICWP_APP_Api_Internal_Core_Update extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$this->loadWpUpgrades();
		$oWp = $this->loadWpFunctions();
		$aActionParams = $this->getActionParams();
		$sVersion = $aActionParams[ 'version' ];

		if ( !$oWp->getIfCoreUpdateExists( $sVersion ) ) {
			return $this->success( array(), 'The requested version is not currently available to install.' );
		}

		$oUpgrader = new Core_Upgrader( new ICWP_Upgrader_Skin() );
		$oResult = $oUpgrader->upgrade( $oWp->getCoreUpdateByVersion( $sVersion ) );
		if ( is_wp_error( $oResult ) ) {
			return $this->fail( 'Upgrade failed with error: '.$oResult->get_error_message() );
		}

		// This was added because some people's sites didn't upgrade the database
		$this->loadWpFunctions()->doWpUpgrade();

		return $this->success( array( 'result' => $oResult ) );
	}
}