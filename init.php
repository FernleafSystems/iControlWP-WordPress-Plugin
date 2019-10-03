<?php

require_once( dirname( __FILE__ ).'/lib/vendor/autoload.php' );

class ICWP_Plugin extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_Plugin_Controller
	 */
	protected static $oPluginController;

	/**
	 * @param ICWP_APP_Plugin_Controller $oPluginController
	 */
	public function __construct( ICWP_APP_Plugin_Controller $oPluginController ) {
		self::$oPluginController = $oPluginController;
		$this->getController()->loadAllFeatures();
	}

	/**
	 * @return ICWP_APP_Plugin_Controller
	 */
	public static function getController() {
		return self::$oPluginController;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mDefault
	 *
	 * @return mixed
	 */
	static public function getOption( $sKey, $mDefault = false ) {
		return self::getController()->loadCorePluginFeatureHandler()->getOpt( $sKey, $mDefault );
	}

	/**
	 * @param string $sKey
	 * @param bool $mValue
	 * @return mixed
	 */
	static public function updateOption( $sKey, $mValue ) {
		$oCorePluginFeature = self::getController()->loadCorePluginFeatureHandler();
		$oCorePluginFeature->setOpt( $sKey, $mValue );
		$oCorePluginFeature->savePluginOptions();
		return true;
	}

	/**
	 * @return string
	 */
	static public function GetAssignedToEmail() {
		return self::getController()->loadCorePluginFeatureHandler()->getAssignedTo();
	}

	/**
	 * @return string
	 */
	static public function GetHelpdeskSsoUrl() {
		return self::getController()->loadCorePluginFeatureHandler()->getHelpdeskSsoUrl();
	}

	/**
	 * @return bool
	 */
	public static function GetHandshakingEnabled() {
		return self::getController()->loadCorePluginFeatureHandler()->getCanHandshake();
	}

	/**
	 * @return boolean
	 */
	static public function IsLinked() {
		return self::getController()->loadCorePluginFeatureHandler()->getIsSiteLinked();
	}

	/**
	 * @return integer
	 */
	public static function GetVersion() {
		return self::getController()->getVersion();
	}

	/**
	 * @return ICWP_APP_FeatureHandler_AutoUpdates
	 */
	public static function GetAutoUpdatesSystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'autoupdates' ) );
	}

	/**
	 * @return ICWP_APP_FeatureHandler_GoogleAnalytics
	 */
	public static function GetGoogleAnalyticsSystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'google_analytics' ) );
	}

	/**
	 * @return ICWP_APP_FeatureHandler_Plugin
	 */
	public static function GetPluginSystem() {
		return self::getController()->loadCorePluginFeatureHandler();
	}
	/**
	 * @return ICWP_APP_FeatureHandler_Statistics
	 */
	public static function GetStatsSystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'statistics' ) );
	}

	/**
	 * @return ICWP_APP_FeatureHandler_WhiteLabel
	 */
	public static function GetWhiteLabelSystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'whitelabel' ) );
	}

	/**
	 * @return ICWP_APP_FeatureHandler_Security
	 */
	public static function GetSecuritySystem() {
		return self::getController()->loadFeatureHandler( array( 'slug' => 'security' ) );
	}
}

if ( !class_exists( 'Worpit_Plugin' ) ) {
	class Worpit_Plugin extends ICWP_Plugin {}
}

$oICWP_App_Controller = ICWP_APP_Plugin_Controller::GetInstance( __FILE__ );
if ( !is_null( $oICWP_App_Controller ) ) {
	$g_oWorpit = new ICWP_Plugin( $oICWP_App_Controller );
}