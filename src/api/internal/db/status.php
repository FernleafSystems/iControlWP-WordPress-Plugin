<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Db_Status', false ) ):

	require_once( dirname( dirname( __FILE__ ) ) . ICWP_DS . 'base.php' );

	class ICWP_APP_Api_Internal_Db_Status extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {} // TODO
	}

endif;