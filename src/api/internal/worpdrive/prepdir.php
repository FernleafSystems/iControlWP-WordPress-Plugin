<?php

class ICWP_APP_Api_Internal_Worpdrive_Prepdir extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$sDir = path_join( ABSPATH, 'worpdrive' );
		$oFS = $this->loadFS();
		$bSuccess = !$oFS->exists( $sDir ) || ( $oFS->deleteDir( $sDir ) && $oFS->mkdir( $sDir ) );
		return $bSuccess ? $this->success() : $this->fail();
	}
}