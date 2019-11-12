<?php

class ICWP_APP_Api_Internal_Db_Status extends ICWP_APP_Api_Internal_Db_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		try {
			$aDataResults = $this->getDatabaseTableStatus();
		}
		catch ( Exception $oE ) {
			return $this->fail( $oE->getMessage() );
		}
		return $this->success( $aDataResults );
	}
}