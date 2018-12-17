<?php

if ( class_exists( 'ICWP_APP_Foundation', false ) ) {
	return;
}

class ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_DataProcessor
	 */
	private static $oDp;

	/**
	 * @var ICWP_APP_WpFilesystem
	 */
	private static $oFs;

	/**
	 * @var ICWP_APP_WpCron
	 */
	private static $oWpCron;

	/**
	 * @var ICWP_APP_WpFunctions
	 */
	private static $oWp;

	/**
	 * @var ICWP_APP_WpFunctions_Plugins
	 */
	private static $oWpPlugins;

	/**
	 * @var ICWP_APP_WpFunctions_Themes
	 */
	private static $oWpThemes;

	/**
	 * @var ICWP_APP_WpDb
	 */
	private static $oWpDb;

	/**
	 * @var ICWP_APP_Render
	 */
	private static $oRender;

	/**
	 * @var ICWP_APP_YamlProcessor
	 */
	private static $oYaml;

	/**
	 * @var ICWP_APP_Encrypt
	 */
	private static $oEncrypt;

	/**
	 * @var ICWP_APP_Ip
	 */
	private static $oIp;

	/**
	 * @var ICWP_APP_GoogleAuthenticator
	 */
	private static $oGA;

	/**
	 * @var ICWP_APP_WpAdminNotices
	 */
	private static $oAdminNotices;

	/**
	 * @var ICWP_APP_WpUsers
	 */
	private static $oWpUsers;

	/**
	 * @var ICWP_APP_WpComments
	 */
	private static $oWpComments;

	/**
	 * @var ICWP_APP_GoogleRecaptcha
	 */
	private static $oGR;

	/**
	 * @var ICWP_APP_WpTrack
	 */
	private static $oTrack;

	/**
	 * @var ICWP_APP_WpUpgrades
	 */
	private static $oUpgrades;

	/**
	 * @return ICWP_APP_DataProcessor
	 */
	static public function loadDataProcessor() {
		if ( !isset( self::$oDp ) ) {
			require_once( dirname( __FILE__ ).'/icwp-data.php' );
			self::$oDp = ICWP_APP_DataProcessor::GetInstance();
		}

		return self::$oDp;
	}

	/**
	 * @return ICWP_APP_WpFilesystem
	 */
	static public function loadFS() {
		if ( !isset( self::$oFs ) ) {
			require_once( dirname( __FILE__ ).'/icwp-wpfilesystem.php' );
			self::$oFs = ICWP_APP_WpFilesystem::GetInstance();
		}

		return self::$oFs;
	}

	/**
	 * @return ICWP_APP_WpFunctions
	 */
	static public function loadWpFunctions() {
		if ( !isset( self::$oWp ) ) {
			require_once( dirname( __FILE__ ).'/icwp-wpfunctions.php' );
			self::$oWp = ICWP_APP_WpFunctions::GetInstance();
		}

		return self::$oWp;
	}

	/**
	 * @return ICWP_APP_WpCron
	 */
	static public function loadWpCronProcessor() {
		if ( !isset( self::$oWpCron ) ) {
			require_once( dirname( __FILE__ ).'/icwp-wpcron.php' );
			self::$oWpCron = ICWP_APP_WpCron::GetInstance();
		}

		return self::$oWpCron;
	}

	/**
	 * @return void
	 */
	static public function loadWpWidgets() {
		require_once( dirname( __FILE__ ).'/wp-widget.php' );
	}

	/**
	 * @return ICWP_APP_WpFunctions_Plugins
	 */
	public function loadWpFunctionsPlugins() {
		if ( !isset( self::$oWpPlugins ) ) {
			require_once( dirname( __FILE__ ).'/icwp-wpfunctions-plugins.php' );
			self::$oWpPlugins = ICWP_APP_WpFunctions_Plugins::GetInstance();
		}

		return self::$oWpPlugins;
	}

	/**
	 * @return ICWP_APP_WpFunctions_Themes
	 */
	public function loadWpFunctionsThemes() {
		if ( !isset( self::$oWpThemes ) ) {
			require_once( dirname( __FILE__ ).'/icwp-wpfunctions-themes.php' );
			self::$oWpThemes = ICWP_APP_WpFunctions_Themes::GetInstance();
		}

		return self::$oWpThemes;
	}

	/**
	 * @return ICWP_APP_Encrypt
	 */
	public function loadEncryptProcessor() {
		if ( !isset( self::$oEncrypt ) ) {
			require_once( dirname( __FILE__ ).'/icwp-encrypt.php' );
			self::$oEncrypt = ICWP_APP_Encrypt::GetInstance();
		}

		return self::$oEncrypt;
	}

	/**
	 * @return ICWP_APP_WpDb
	 */
	static public function loadDbProcessor() {
		if ( !isset( self::$oWpDb ) ) {
			require_once( dirname( __FILE__ ).'/icwp-wpdb.php' );
			self::$oWpDb = ICWP_APP_WpDb::GetInstance();
		}

		return self::$oWpDb;
	}

	/**
	 * @return ICWP_APP_Ip
	 */
	static public function loadIpProcessor() {
		if ( !isset( self::$oIp ) ) {
			require_once( dirname( __FILE__ ).'/icwp-ip.php' );
			self::$oIp = ICWP_APP_Ip::GetInstance();
		}

		return self::$oIp;
	}

	/**
	 * @return ICWP_APP_GoogleAuthenticator
	 */
	static public function loadGoogleAuthenticatorProcessor() {
		if ( !isset( self::$oGA ) ) {
			require_once( dirname( __FILE__ ).'/icwp-googleauthenticator.php' );
			self::$oGA = ICWP_APP_GoogleAuthenticator::GetInstance();
		}

		return self::$oGA;
	}

	/**
	 * @return ICWP_APP_GoogleRecaptcha
	 */
	static public function loadGoogleRecaptcha() {
		if ( !isset( self::$oGR ) ) {
			require_once( dirname( __FILE__ ).'/icwp-googlearecaptcha.php' );
			self::$oGR = ICWP_APP_GoogleRecaptcha::GetInstance();
		}

		return self::$oGR;
	}

	/**
	 * @return ICWP_APP_WpTrack
	 */
	static public function loadWpTrack() {
		if ( !isset( self::$oTrack ) ) {
			require_once( dirname( __FILE__ ).'/wp-track.php' );
			self::$oTrack = ICWP_APP_WpTrack::GetInstance();
		}

		return self::$oTrack;
	}

	/**
	 * @param string $sTemplatePath
	 * @return ICWP_APP_Render
	 */
	static public function loadRenderer( $sTemplatePath = '' ) {
		if ( !isset( self::$oRender ) ) {
			require_once( dirname( __FILE__ ).'/icwp-render.php' );
			self::$oRender = ICWP_APP_Render::GetInstance()
											->setAutoloaderPath( dirname( __FILE__ ).'/Twig/Autoloader.php' );
		}
		if ( !empty( $sTemplatePath ) ) {
			self::$oRender->setTemplateRoot( $sTemplatePath );
		}

		return self::$oRender;
	}

	/**
	 * @return ICWP_APP_YamlProcessor
	 */
	static public function loadYamlProcessor() {
		if ( !isset( self::$oYaml ) ) {
			require_once( dirname( __FILE__ ).'/icwp-yaml.php' );
			self::$oYaml = ICWP_APP_YamlProcessor::GetInstance();
		}

		return self::$oYaml;
	}

	/**
	 * @return ICWP_APP_WpAdminNotices
	 */
	static public function loadAdminNoticesProcessor() {
		if ( !isset( self::$oAdminNotices ) ) {
			require_once( dirname( __FILE__ ).'/wp-admin-notices.php' );
			self::$oAdminNotices = ICWP_APP_WpAdminNotices::GetInstance();
		}

		return self::$oAdminNotices;
	}

	/**
	 * @return ICWP_APP_WpUsers
	 */
	static public function loadWpUsersProcessor() {
		if ( !isset( self::$oWpUsers ) ) {
			require_once( dirname( __FILE__ ).'/wp-users.php' );
			self::$oWpUsers = ICWP_APP_WpUsers::GetInstance();
		}

		return self::$oWpUsers;
	}

	/**
	 * @return ICWP_APP_WpUpgrades
	 */
	static public function loadWpUpgrades() {
		if ( !isset( self::$oUpgrades ) ) {
			require_once( dirname( __FILE__ ).'/icwp-wpupgrades.php' );
			self::$oUpgrades = ICWP_APP_WpUpgrades::GetInstance();
		}

		return self::$oUpgrades;
	}

	/**
	 * @return ICWP_APP_WpComments
	 */
	static public function loadWpCommentsProcessor() {
		if ( !isset( self::$oWpComments ) ) {
			require_once( dirname( __FILE__ ).'/wp-comments.php' );
			self::$oWpComments = ICWP_APP_WpComments::GetInstance();
		}

		return self::$oWpComments;
	}

	/**
	 * @return ICWP_Stats_APP
	 */
	public function loadStatsProcessor() {
		require_once( dirname( __FILE__ ).'/icwp-stats.php' );
	}
}