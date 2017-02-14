<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_User_Login', false ) ):

	require_once( dirname( dirname( __FILE__ ) ) . ICWP_DS . 'base.php' );

	class ICWP_APP_Api_Internal_User_Login extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {
			$sSource = home_url().'$'.uniqid().'$'.time();
			$sToken = md5( $sSource );
			$this->loadWpFunctionsProcessor()->setTransient( 'icwplogintoken', $sToken, MINUTE_IN_SECONDS );
			$aData = array(
				'source'	=> $sSource,
				'token'		=> $sToken
			);
			return $this->success( $aData );
		}
	}

endif;