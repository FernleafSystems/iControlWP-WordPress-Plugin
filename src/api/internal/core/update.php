<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\Modules\Autoupdates;

class ICWP_APP_Api_Internal_Core_Update extends ICWP_APP_Api_Internal_Base {

	use Autoupdates\Lib\AutoOrLegacyUpdater;

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$this->loadWpUpgrades();
		$oWP = $this->loadWP();
		$sVersion = $this->getActionParam( 'version' );

		if ( !$oWP->getIfCoreUpdateExists( $sVersion ) ) {
			return $this->success( [], 'The requested version is not currently available to install.' );
		}

		$oCoreUpdate = $oWP->getCoreUpdateByVersion( $sVersion );
		if ( is_wp_error( $oCoreUpdate ) ) {
			return $this->fail( 'Upgrade failed with error: '.$oCoreUpdate->get_error_message() );
		}

		$oResult = $this->isMethodAuto() ? $this->processAuto( $oCoreUpdate ) : $this->processLegacy( $oCoreUpdate );

		if ( !$sVersion === $oWP->getWordpressVersion( true ) ) {
			return $this->fail( 'Upgrade Failed', -1, [
				'result' => $oResult,
			] );
		}

		// This was added because some sites didn't upgrade the database
		$this->loadWP()->doWpUpgrade();

		return $this->success( [
			'success' => 1,
			'result'  => $oResult,
		] );
	}

	/**
	 * @param string|object $oCoreUpdate
	 */
	protected function processAuto( $oCoreUpdate ) {
		( new Autoupdates\Lib\RunAutoupdates() )->core( $oCoreUpdate );
	}

	/**
	 * @param $oCoreUpdate
	 * @return false|string|\WP_Error
	 */
	protected function processLegacy( $oCoreUpdate ) {
		$oSkin = $this->loadWP()->getWordpressIsAtLeastVersion( '3.7' ) ?
			new \Automatic_Upgrader_Skin()
			: new \ICWP_Upgrader_Skin();
		return ( new Core_Upgrader( $oSkin ) )->upgrade( $oCoreUpdate );
	}
}