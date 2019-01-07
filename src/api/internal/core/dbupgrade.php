<?php

if ( class_exists( 'ICWP_APP_Api_Internal_Core_Dbupgrade', false ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

class ICWP_APP_Api_Internal_Core_Dbupgrade extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$this->loadWpFunctions()->doWpUpgrade();
		return $this->success();
	}
}