<?php

if ( class_exists( 'ICWP_APP_Api_Internal_Theme_Update', false ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

class ICWP_APP_Api_Internal_Theme_Update extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$this->importCommonLib( 'plugins' );
		$aActionParams = $this->getActionParams();
		$sAssetFile = $aActionParams[ 'theme_file' ];

		if ( $aActionParams[ 'do_rollback_prep' ] ) {
			$oPluginsCommon = new ICWP_APP_Api_Internal_Common_Plugins();
			$fRollbackResult = $oPluginsCommon->prepRollbackData( $sAssetFile, 'plugins' );
		}

		$aResult = $this->loadWpFunctionsThemes()->update( $sAssetFile );
		if ( isset( $aResult[ 'successful' ] ) && $aResult[ 'successful' ] == 0 ) {
			return $this->fail( implode( ' | ', $aResult[ 'errors' ] ), $aResult );
		}

		$aData = array(
			'rollback' => isset( $fRollbackResult ) ? $fRollbackResult : false,
			'result'   => $aResult,
			'normal'   => $sAssetFile,
		);
		return $this->success( $aData );
	}
}