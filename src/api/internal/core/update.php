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

		if ( !$oWp->getHasCoreUpdatesAvailable() ) {
			return $this->success( array(), 'There is no update available' );
		}

		$sCoreUpdateType = $oWp->getCoreUpdateType();
		if ( $sCoreUpdateType == 'latest' ) {
			return $this->success( array(), 'Already on the latest version of WordPress' );
		}
		if ( $sCoreUpdateType == 'development' ) {
			return $this->success( array(), 'The latest version available is a development version of WordPress' );
		}

		$oChosenUpdate = $oWp->getChosenCoreUpdate();
		if ( !$oChosenUpdate ) {
			return $this->fail( 'Failed to find an update' );
		}

		$oUpgrader = new Core_Upgrader( new ICWP_Upgrader_Skin() );
		$oResult = $oUpgrader->upgrade( $oChosenUpdate );
		if ( is_wp_error( $oResult ) ) {
			return $this->fail( 'Upgrade failed with error: '.$oResult->get_error_message() );
		}

		// This was added because some people's sites didn't upgrade the database
		if ( function_exists( 'wp_upgrade' ) ) {
			wp_upgrade();
		}
		else {
			return $this->fail( 'wp_upgrade function not available.' );
		}

		$aData = array(
			'result' => $oResult
		);
		return $this->success( $aData );
	}
}