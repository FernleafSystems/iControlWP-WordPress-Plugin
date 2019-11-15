<?php

class ICWP_APP_Api_Internal_Theme_Update extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$aActionParams = $this->getActionParams();
		$sAssetFile = $aActionParams[ 'theme_file' ];

		if ( $aActionParams[ 'do_rollback_prep' ] ) {
			$oPluginsCommon = new ICWP_APP_Api_Internal_Common_Plugins();
			$bRollback = $oPluginsCommon->prepRollbackData( $sAssetFile, 'plugins' );
		}

		$aResult = $this->loadWpFunctionsThemes()->update( $sAssetFile );
		if ( empty( $aResult[ 'successful' ] ) ) {
			return $this->fail( implode( ' | ', $aResult[ 'errors' ] ), -1, $aResult );
		}

		$aData = [
			'rollback' => $bRollback ?? false,
			'result'   => $aResult,
			'normal'   => $sAssetFile,
		];
		return $this->success( $aData );
	}
}