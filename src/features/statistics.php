<?php

class ICWP_APP_FeatureHandler_Statistics extends ICWP_APP_FeatureHandler_BaseApp {

	/**
	 * @return array
	 */
	public function retrieveDailyStats() {
		/** @var ICWP_APP_Processor_Statistics $oFp */
		$oFp = $this->getProcessor();
		return $oFp->getDailyTotals();
	}

	/**
	 * @return array
	 */
	public function retrieveMonthlyStats() {
		/** @var ICWP_APP_Processor_Statistics $oFp */
		$oFp = $this->getProcessor();
		return $oFp->getMonthlyTotals();
	}

	/**
	 * @return string
	 */
	public function getStatisticsTableName() {
		return $this->doPluginPrefix( $this->getDefinition( 'statistics_table_name' ), '_' );
	}
}