<?php

class ICWP_APP_WpUpgrades extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpUpgrades
	 */
	protected static $oInstance = null;

	/**
	 * @return ICWP_APP_WpUpgrades
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}
}

if ( !class_exists( 'WP_Upgrader_Skin', false ) ) {
	$sWordPressWpUpgraderClass = ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
	if ( !is_file( $sWordPressWpUpgraderClass ) ) {
		die( '-9999:Failed to find required WP_Upgrader_Skin at '.$sWordPressWpUpgraderClass );
	}
	include_once( $sWordPressWpUpgraderClass );
}