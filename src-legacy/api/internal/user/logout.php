<?php

if ( class_exists( 'ICWP_APP_Api_Internal_User_Logout', false ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

class ICWP_APP_Api_Internal_User_Logout extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$this->loadWpUsers()->logoutUser();
		return $this->success();
	}
}