<?php

class ICWP_APP_Api_Internal_User_Logout extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$this->loadWpUsers()->logoutUser();
		return $this->success();
	}
}