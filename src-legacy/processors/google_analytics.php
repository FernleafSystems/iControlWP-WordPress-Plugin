<?php

if ( class_exists( 'ICWP_APP_Processor_GoogleAnalytics', false ) ) {
	return;
}

require_once( dirname( __FILE__ ).'/base_app.php' );

class ICWP_APP_Processor_GoogleAnalytics extends ICWP_APP_Processor_BaseApp {

	/**
	 * @var array
	 */
	private $aGaOptions;

	public function run() {
		add_action( 'wp', array( $this, 'onWp' ) );
	}

	public function onWp() {
		$this->migrateOptions();

		$sID = $this->getTrackingId();
		if ( !empty( $sID ) && !$this->getIfIgnoreUser() && $this->isValidAnalyticsMode() ) {
			add_action( $this->getWpHook(), array( $this, 'printGoogleAnalytics' ), 100 );
			if ( $this->getAnalyticsMode() == 'tagman' ) {
				add_action( 'wp_body_open', array( $this, 'printTagManBody' ) );
			}
		}
	}

	/**
	 * Added with 3.7.0
	 */
	private function migrateOptions() {
		$oMod = $this->getFeatureOptions();
		if ( $oMod->getOpt( 'analytics_mode' ) == 'unset' ) {
			$oMod->setOpt(
				'analytics_mode',
				( $oMod->getOpt( 'enable_universal_analytics' ) == 'Y' ) ? 'universal' : 'classic'
			);
		}
	}

	/**
	 * @return void
	 */
	public function printGoogleAnalytics() {
		echo $this->parseAnalyticsSnippet( $this->getAnalyticsMode() );
	}

	/**
	 * @param string $sType
	 * @return string
	 */
	private function parseAnalyticsSnippet( $sType ) {
		return $this->getFeatureOptions()
					->renderTemplate(
						sprintf( 'snippets/analytics_%s.php', strtolower( $sType ) ),
						array( 'tid' => $this->getTrackingId() )
					);
	}

	/**
	 * @return string
	 */
	private function getAnalyticsMode() {
		$aOpts = $this->getGaOpts();
		return $aOpts[ 'analytics_mode' ];
	}

	/**
	 * @return string
	 */
	private function getTrackingId() {
		$aOpts = $this->getGaOpts();
		return $aOpts[ 'tracking_id' ];
	}

	/**
	 * @return array
	 */
	private function getGaOpts() {
		$oMod = $this->getFeatureOptions();
		if ( empty( $this->aGaOptions ) ) {
			$this->aGaOptions = array(
				'tracking_id'            => $oMod->getOpt( 'tracking_id' ),
				'analytics_mode'         => strtolower( $oMod->getOpt( 'analytics_mode' ) ),
				'ignore_logged_in_user'  => $oMod->getOpt( 'ignore_logged_in_user' ),
				'ignore_from_user_level' => $oMod->getOpt( 'ignore_from_user_level', 11 ),
				'in_footer'              => $oMod->getOpt( 'in_footer' ),
			);
		}
		return $this->aGaOptions;
	}

	/**
	 * @return bool
	 */
	private function getIfIgnoreUser() {
		$bIgnore = false;

		$aOpts = $this->getGaOpts();
		$nCurrentUserLevel = $this->loadWpUsers()->getCurrentUserLevel();
		if ( ( $aOpts[ 'ignore_logged_in_user' ] == 'Y' ) && $nCurrentUserLevel >= 0 ) { // logged in
			$nIgnoreFromUserLevel = $aOpts[ 'ignore_from_user_level' ];
			if ( $nCurrentUserLevel >= $nIgnoreFromUserLevel ) {
				$bIgnore = true;
			}
		}

		return $bIgnore;
	}

	public function isValidAnalyticsMode() {
		return in_array( $this->getAnalyticsMode(), array( 'classic', 'sitetag', 'tagman', 'universal' ) );
	}

	/**
	 */
	public function printTagManBody() {
		return $this->parseAnalyticsSnippet( 'tagman_body' );
	}

	/**
	 * @return string
	 */
	private function getWpHook() {
		$aOpts = $this->getGaOpts();
		$sHook = 'wp_head';
		if ( $this->getAnalyticsMode() != 'tagman' && $aOpts[ 'in_footer' ] == 'Y' ) {
			$sHook = 'wp_print_footer_scripts';
		}
		return $sHook;
	}
}