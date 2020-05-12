<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\Modules\Autoupdates;

class ICWP_APP_Api_Internal_Theme_Update extends ICWP_APP_Api_Internal_Base {

	use Autoupdates\Lib\AutoOrLegacyUpdater;

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$bSuccess = false;

		$sFile = $this->getActionParam( 'theme_file' );
		$aData = [
			'rollback' => false,
		];

		$oWpThemes = $this->loadWpFunctionsThemes();
		$oTheme = $oWpThemes->getTheme( $sFile );
		if ( !empty( $oTheme ) ) {
			$aData[ 'rollback' ] = $this->getActionParam( 'do_rollback_prep' )
								   && ( new ICWP_APP_Api_Internal_Common_Plugins() )->prepRollbackData( $sFile, 'themes' );

			$sPreVersion = $oTheme->get( 'Version' );

			$this->isMethodAuto() ? $this->processAuto( $sFile ) : $this->processLegacy( $sFile );

			$oTheme = $oWpThemes->getTheme( $sFile );
			$bSuccess = !empty( $oTheme ) && $sPreVersion !== $oTheme->get( 'Version' );
		}

		return $bSuccess ? $this->success( $aData ) : $this->fail( 'Update failed', -1, $aData );
	}

	/**
	 * @param string $mAsset
	 */
	protected function processAuto( $mAsset ) {
		( new Autoupdates\Lib\RunAutoupdates() )->theme( $mAsset );
	}

	/**
	 * @param string $mAsset
	 * @return mixed[]
	 */
	protected function processLegacy( $mAsset ) {
		return $this->loadWpFunctionsThemes()->update( $mAsset );
	}
}