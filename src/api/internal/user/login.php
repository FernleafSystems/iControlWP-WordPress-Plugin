<?php

class ICWP_APP_Api_Internal_User_Login extends ICWP_APP_Api_Internal_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {
		$sSource = home_url().'$'.uniqid().'$'.time();
		$sToken = md5( $sSource );
		$this->loadWP()->setTransient( 'icwplogintoken', $sToken, MINUTE_IN_SECONDS );
		$aData = [
			'source' => $sSource,
			'token'  => $sToken
		];
		return $this->success( $aData );
	}
}