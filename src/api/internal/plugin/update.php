<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\Modules\Autoupdates;

class ICWP_APP_Api_Internal_Plugin_Update extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$bSuccess = false;
		$aActionParams = $this->getActionParams();

		$sAssetFile = $aActionParams[ 'plugin_file' ];
		$aData = [
			'rollback'      => false,
			'method_auto'   => false,
			'method_legacy' => false,
		];

		$oWpPlugins = $this->loadWpFunctionsPlugins();
		$aPlugin = $oWpPlugins->getPlugin( $sAssetFile );
		if ( !empty( $aPlugin ) ) {
			$aData[ 'rollback' ] = $aActionParams[ 'do_rollback_prep' ]
								   && ( new ICWP_APP_Api_Internal_Common_Plugins() )
									   ->prepRollbackData( $sAssetFile, 'plugins' );

			$bWasActive = $oWpPlugins->getIsActive( $sAssetFile );
			$sPreV = $aPlugin[ 'Version' ];

			$bUseAuto = $this->loadWP()->getWordpressIsAtLeastVersion( '3.8.2' )
						&& empty( $aActionParams[ 'use_legacy' ] );
			if ( $bUseAuto ) {
				$this->processAutoMethod( $sAssetFile );
				$aPlugin = $oWpPlugins->getPlugin( $sAssetFile );
				$bSuccess = !empty( $aPlugin ) && $sPreV !== $aPlugin[ 'Version' ];
				$aData[ 'method_auto' ] = $bSuccess;
			}

			if ( !empty( $aPlugin ) && !$bSuccess && $bUseAuto ) {
				$this->processLegacy( $sAssetFile );
				$aPlugin = $oWpPlugins->getPlugin( $sAssetFile );
				$bSuccess = !empty( $aPlugin ) && $sPreV !== $aPlugin[ 'Version' ];
				$aData[ 'method_legacy' ] = $bSuccess;
			}

			if ( $bSuccess && $bWasActive && !$oWpPlugins->getIsActive( $sAssetFile ) ) {
				activate_plugin( $sAssetFile );
			}
		}

		return $bSuccess ?
			$this->success( $aData )
			: $this->fail( 'Update failed', -1, $aData );
	}

	/**
	 * @param $sAssetFile
	 */
	public function processAutoMethod( $sAssetFile ) {
		( new Autoupdates\Lib\RunAutoupdates() )->plugin( $sAssetFile );
	}

	/**
	 * @param $sAssetFile
	 * @return mixed[]
	 */
	public function processLegacy( $sAssetFile ) {

		// handles manual Third Party Update Checking.
//			$oWpUpdatesHandler->prepThirdPartyPlugins();

		// For some reason, certain updates don't appear and we may have to force an update check to ensure WordPress
		// knows about the update.
		$oAvailableUpdates = $this->loadWP()->updatesGather( 'plugins' );
		if ( empty( $oAvailableUpdates ) || empty( $oAvailableUpdates->response[ $sAssetFile ] ) ) {
			$this->loadWP()->updatesCheck( 'plugins', true );
		}

		return $this->loadWpFunctionsPlugins()->update( $sAssetFile );
	}
}