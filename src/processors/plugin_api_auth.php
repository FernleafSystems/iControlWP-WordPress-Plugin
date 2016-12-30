<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api_Auth', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'plugin_api.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api_Index
	 */
	class ICWP_APP_Processor_Plugin_Api_Auth extends ICWP_APP_Processor_Plugin_Api {

		/**
		 * @return ApiResponse
		 */
		protected function processAction() {
			return $this->doAuth();
		}

		/**
		 * @return ApiResponse
		 */
		protected function doAuth() {
			$this->setAuthorizedUser();
			$this->setWpEngineAuth();
			return $this->setSuccessResponse( 'Auth' ); //just to be sure we proceed thereafter
		}
	}

endif;
