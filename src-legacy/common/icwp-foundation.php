<?php

if ( class_exists( 'ICWP_APP_Foundation', false ) ) {
	return;
}

class ICWP_APP_Foundation {

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
	 * @var ICWP_APP_Encrypt
	 */
	private static $oEncrypt;

	/**
	 * @var ICWP_APP_Ip
	 */
	private static $oIp;

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
	 * @var ICWP_APP_WpUpgrades
	 */
	private static $oUpgrades;

	/**
	 * @return ICWP_APP_DataProcessor
	 */
	static public function loadDP() {
		require_once( dirname( __FILE__ ).'/icwp-data.php' );
		return ICWP_APP_DataProcessor::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFilesystem
	 */
	static public function loadFS() {
		require_once( dirname( __FILE__ ).'/icwp-wpfilesystem.php' );
		return ICWP_APP_WpFilesystem::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFunctions
	 */
	static public function loadWP() {
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
	 * @return ICWP_APP_WpTrack
	 * @deprecated 3.7
	 */
	static public function loadWpTrack() {
		require_once( dirname( __FILE__ ).'/wp-track.php' );
		return ICWP_APP_WpTrack::GetInstance();
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
	static public function loadWpUsers() {
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
	 * @return ICWP_APP_DataProcessor
	 * @deprecated 3.7
	 */
	static public function loadDataProcessor() {
		return self::loadDP();
	}

	/**
	 * @return ICWP_APP_WpFunctions
	 * @deprecated 3.7
	 */
	static public function loadWpFunctions() {
		return self::loadWP();
	}

	/**
	 * @return ICWP_APP_WpUsers
	 * @deprecated 3.7
	 */
	static public function loadWpUsersProcessor() {
		return self::loadWpUsers();
	}
}