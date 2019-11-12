<?php

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
