<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Plugin;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class Update extends LegacyApi\Internal\Base {

	use LegacyApi\Internal\Common\AutoOrLegacyUpdater;
	use LegacyApi\Internal\Common\Rollback;

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function process() {
		$bSuccess = false;

		$sFile = $this->getActionParam( 'plugin_file' );
		$aData = [
			'rollback' => false,
		];

		$oWpPlugins = $this->loadWpFunctionsPlugins();
		$aPlugin = $oWpPlugins->getPlugin( $sFile );
		if ( !empty( $aPlugin ) ) {
			$aData[ 'rollback' ] = $this->getActionParam( 'do_rollback_prep' )
								   && $this->prepRollbackData( $sFile, 'plugins' );

			$bWasActive = $oWpPlugins->getIsActive( $sFile );
			$sPreVersion = $aPlugin[ 'Version' ];

			$this->isMethodAuto() ? $this->processAuto( $sFile ) : $this->processLegacy( $sFile );

			$aPlugin = $oWpPlugins->getPlugin( $sFile );
			$bSuccess = !empty( $aPlugin ) && $sPreVersion !== $aPlugin[ 'Version' ];

			if ( $bSuccess && $bWasActive && !$oWpPlugins->getIsActive( $sFile ) ) {
				activate_plugin( $sFile );
			}
		}

		return $bSuccess ? $this->success( $aData ) : $this->fail( 'Update failed', -1, $aData );
	}

	/**
	 * @param string $mAsset
	 */
	protected function processAuto( $mAsset ) {
		( new LegacyApi\Internal\Common\RunAutoupdates() )->plugin( $mAsset );
	}

	/**
	 * @param string $mAsset
	 * @return mixed[]
	 */
	protected function processLegacy( $mAsset ) {

		// handles manual Third Party Update Checking.
//			$oWpUpdatesHandler->prepThirdPartyPlugins();

		// For some reason, certain updates don't appear and we may have to force an update check to ensure WordPress
		// knows about the update.
		$oAvailableUpdates = $this->loadWP()->updatesGather( 'plugins' );
		if ( empty( $oAvailableUpdates ) || empty( $oAvailableUpdates->response[ $mAsset ] ) ) {
			$this->loadWP()->updatesCheck( 'plugins', true );
		}

		return $this->loadWpFunctionsPlugins()->update( $mAsset );
	}
}