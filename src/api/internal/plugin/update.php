<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Plugin_Update', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Plugin_Update extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {
			$this->importCommonLib( 'plugins' );
			$aActionParams = $this->getActionParams();
			$sAssetFile = $aActionParams[ 'plugin_file' ];

			$aData = array();

			// handles manual Third Party Update Checking.
//			$oWpUpdatesHandler->prepThirdPartyPlugins();

			// For some reason, certain updates don't appear and we may have to force an update check to ensure WordPress
			// knows about the update.
			$oAvailableUpdates = $this->loadWpFunctions()->updatesGather( 'plugins' );
			if ( empty( $oAvailableUpdates ) || empty( $oAvailableUpdates->response[ $sAssetFile ] ) ) {
				$this->loadWpFunctions()->updatesCheck( 'plugins', true );
				$aData[ 'force_update_recheck' ] = 1;
			}

			if ( $aActionParams[ 'do_rollback_prep' ] ) {
				$oPluginsCommon = new ICWP_APP_Api_Internal_Common_Plugins();
				$fRollbackResult = $oPluginsCommon->prepRollbackData( $sAssetFile, 'plugins' );
			}

			$aResult = $this->loadWpFunctionsPlugins()->update( $sAssetFile );

			if ( isset( $aResult['successful'] ) && $aResult['successful'] == 0 ) {
				return $this->fail( implode( ' | ', $aResult['errors'] ), $aResult );
			}

			$aData[ 'rollback' ] = isset( $fRollbackResult ) ? $fRollbackResult : false;
			$aData[ 'result' ] = $aResult;
			return $this->success( $aData );
		}
	}

endif;