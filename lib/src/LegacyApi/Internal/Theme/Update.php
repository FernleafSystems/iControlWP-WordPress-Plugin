<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\Internal\Theme;

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class Update extends LegacyApi\Internal\Base {

	use LegacyApi\Internal\Common\AutoOrLegacyUpdater;
	use LegacyApi\Internal\Common\Rollback;

	/**
	 * @return LegacyApi\ApiResponse
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
								   && $this->prepRollbackData( $sFile, 'themes' );

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
		( new LegacyApi\Internal\Common\RunAutoupdates() )->theme( $mAsset );
	}

	/**
	 * @param string $mAsset
	 * @return mixed[]
	 */
	protected function processLegacy( $mAsset ) {
		return $this->loadWpFunctionsThemes()->update( $mAsset );
	}
}