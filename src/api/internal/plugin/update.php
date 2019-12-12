<?php

class ICWP_APP_Api_Internal_Plugin_Update extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$aActionParams = $this->getActionParams();
		$sAssetFile = $aActionParams[ 'plugin_file' ];

		$aData = [];

		// handles manual Third Party Update Checking.
//			$oWpUpdatesHandler->prepThirdPartyPlugins();

		// For some reason, certain updates don't appear and we may have to force an update check to ensure WordPress
		// knows about the update.
		$oAvailableUpdates = $this->loadWP()->updatesGather( 'plugins' );
		if ( empty( $oAvailableUpdates ) || empty( $oAvailableUpdates->response[ $sAssetFile ] ) ) {
			$this->loadWP()->updatesCheck( 'plugins', true );
			$aData[ 'force_update_recheck' ] = 1;
		}

		if ( $aActionParams[ 'do_rollback_prep' ] ) {
			$oPluginsCommon = new ICWP_APP_Api_Internal_Common_Plugins();
			$fRollbackResult = $oPluginsCommon->prepRollbackData( $sAssetFile, 'plugins' );
		}

		$oWpPlugins = $this->loadWpFunctionsPlugins();
		$bWasActive = $oWpPlugins->getIsActive( $sAssetFile );

		$aResult = $oWpPlugins->update( $sAssetFile );
		if ( empty( $aResult[ 'successful' ] ) ) {
			return $this->fail( implode( ' | ', $aResult[ 'errors' ] ), -1, $aResult );
		}

		if ( $bWasActive && !$oWpPlugins->getIsActive( $sAssetFile ) ) {
			activate_plugin( $bWasActive );
		}

		$aData[ 'rollback' ] = isset( $fRollbackResult ) ? $fRollbackResult : false;
		$aData[ 'result' ] = $aResult;
		return $this->success( $aData );
	}
}