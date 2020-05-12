<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\Modules\Autoupdates;

class ICWP_APP_Api_Internal_Theme_Update extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$bSuccess = false;
		$aActionParams = $this->getActionParams();

		$sAssetFile = $aActionParams[ 'theme_file' ];
		$aData = [
			'rollback'      => false,
			'method_auto'   => false,
			'method_legacy' => false,
		];

		$oWpThemes = $this->loadWpFunctionsThemes();
		$oTheme = $oWpThemes->getTheme( $sAssetFile );
		if ( !empty( $oTheme ) ) {
			$aData[ 'rollback' ] = $aActionParams[ 'do_rollback_prep' ]
								   && ( new ICWP_APP_Api_Internal_Common_Plugins() )
									   ->prepRollbackData( $sAssetFile, 'plugins' );

			$sPreV = $oTheme->get( 'Version' );

			$bUseAuto = $this->loadWP()->getWordpressIsAtLeastVersion( '3.8.2' )
						&& $aActionParams[ 'update_method' ] !== 'legacy';
			if ( $bUseAuto ) {
				$this->processAutoMethod( $sAssetFile );
				$oTheme = $oWpThemes->getTheme( $sAssetFile );
				$bSuccess = !empty( $oTheme ) && $sPreV !== $oTheme->get( 'Version' );
				$aData[ 'update_method' ] = 'auto';
			}
			else {
				$this->processLegacy( $sAssetFile );
				$oTheme = $oWpThemes->getTheme( $sAssetFile );
				$bSuccess = !empty( $oTheme ) && $sPreV !== $oTheme->get( 'Version' );
				$aData[ 'update_method' ] = 'legacy';
			}
		}

		return $bSuccess ? $this->success( $aData ) : $this->fail( 'Update failed', -1, $aData );
	}

	/**
	 * @param string $sAssetFile
	 */
	public function processAutoMethod( $sAssetFile ) {
		( new Autoupdates\Lib\RunAutoupdates() )->theme( $sAssetFile );
	}

	/**
	 * @param string $sAssetFile
	 * @return mixed[]
	 */
	public function processLegacy( $sAssetFile ) {
		return $this->loadWpFunctionsThemes()->update( $sAssetFile );
	}
}