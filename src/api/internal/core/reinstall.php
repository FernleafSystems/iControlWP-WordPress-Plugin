<?php

class ICWP_APP_Api_Internal_Core_Reinstall extends ICWP_APP_Api_Internal_Base {

	/**
	 * @see wp-admin/update-core.php
	 * @return ApiResponse
	 */
	public function process() {
		$this->loadWpUpgrades();
		$oWp = $this->loadWpFunctions();

		$oWpCoreUpdate = find_core_update( $oWp->getWordpressVersion(), $oWp->getLocale() );
		if ( empty( $oWpCoreUpdate ) ) {
			return $this->fail( 'Could not find Core Update object/data' );
		}

		$oWpCoreUpdate->response = 'reinstall';
		$oUpgrader = new Core_Upgrader( new ICWP_Upgrader_Skin() );
		$oResult = $oUpgrader->upgrade( $oWpCoreUpdate );
		if ( is_wp_error( $oResult ) ) {
			return $this->fail( 'Re-install failed with error: '.$oResult->get_error_message() );
		}

		return $this->success( array( 'result' => $oResult ) );
	}
}