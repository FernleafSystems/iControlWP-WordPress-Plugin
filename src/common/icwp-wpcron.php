<?php

class ICWP_APP_WpCron {

	/**
	 * @var ICWP_APP_WpCron
	 */
	protected static $oInstance = null;

	private function __construct() {
	}

	/**
	 * @return ICWP_APP_WpCron
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}

	/**
	 * @param string   $sUniqueCronName
	 * @param callback $sCallback
	 * @param string   $sRecurrence
	 * @throws Exception
	 */
	public function createCronJob( $sUniqueCronName, $sCallback, $sRecurrence = 'daily' ) {
		if ( !is_callable( $sCallback ) ) {
			throw new Exception( sprintf( 'Tried to schedule a new cron but the Callback function is not callable: %s', print_r( $sCallback, true ) ) );
		}
		add_action( $sUniqueCronName, $sCallback );
		$this->setCronSchedule( $sUniqueCronName, $sRecurrence );
	}

	/**
	 * @param string $sUniqueCronName
	 */
	public function deleteCronJob( $sUniqueCronName ) {
		wp_clear_scheduled_hook( $sUniqueCronName );
	}

	/**
	 * @param $sUniqueCronActionName
	 * @param $sRecurrence - one of hourly, twicedaily, daily
	 */
	protected function setCronSchedule( $sUniqueCronActionName, $sRecurrence ) {
		if ( !wp_next_scheduled( $sUniqueCronActionName ) && !defined( 'WP_INSTALLING' ) ) {
			$nNextRun = strtotime( 'tomorrow 6am' ) - get_option( 'gmt_offset' )*HOUR_IN_SECONDS;
			wp_schedule_event( $nNextRun, $sRecurrence, $sUniqueCronActionName );
		}
	}
}