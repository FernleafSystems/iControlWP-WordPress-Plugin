<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api_Index', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'plugin_api.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api_Index
	 */
	class ICWP_APP_Processor_Plugin_Api_Index extends ICWP_APP_Processor_Plugin_Api {

		/**
		 * @return ApiResponse
		 */
		protected function processAction() {
			return $this->setSuccessResponse( 'Plugin Index' );
		}
	}

endif;
