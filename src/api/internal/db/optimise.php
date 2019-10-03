<?php

class ICWP_APP_Api_Internal_Db_Optimise extends ICWP_APP_Api_Internal_Db_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		try {
			$aDataResults = $this->optimiseDatabase();
		}
		catch ( Exception $oE ) {
			return $this->fail( $oE->getMessage() );
		}
		return $this->success( $aDataResults );
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function optimiseDatabase() {
		$oDb = $this->loadDbProcessor();

		$aTableStatus = $this->getDatabaseTableStatus();
		if ( empty( $aTableStatus[ 'tables' ] ) ) {
			throw new Exception( 'Empty results from TABLE STATUS query is not expected.' );
		}
		foreach ( $aTableStatus[ 'tables' ] as $aTable ) {
			if ( $aTable[ 'gain' ] > 0 ) {
				$oDb->optimizeTable( $aTable[ 'name' ] );
			}
		}
		return $this->getDatabaseTableStatus();
	}
}