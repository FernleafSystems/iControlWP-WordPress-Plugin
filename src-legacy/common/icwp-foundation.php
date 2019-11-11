<?php

if ( class_exists( 'ICWP_APP_Foundation', false ) ) {
	return;
}

class ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_Render
	 */
	private static $oRender;

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
		require_once( dirname( __FILE__ ).'/icwp-wpfunctions.php' );
		return ICWP_APP_WpFunctions::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpCron
	 */
	static public function loadWpCronProcessor() {
		require_once( dirname( __FILE__ ).'/icwp-wpcron.php' );
		return ICWP_APP_WpCron::GetInstance();
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
		require_once( dirname( __FILE__ ).'/icwp-wpfunctions-plugins.php' );
		return ICWP_APP_WpFunctions_Plugins::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFunctions_Themes
	 */
	public function loadWpFunctionsThemes() {
		require_once( dirname( __FILE__ ).'/icwp-wpfunctions-themes.php' );
		return ICWP_APP_WpFunctions_Themes::GetInstance();
	}

	/**
	 * @return ICWP_APP_Encrypt
	 */
	public function loadEncryptProcessor() {
		require_once( dirname( __FILE__ ).'/icwp-encrypt.php' );
		return ICWP_APP_Encrypt::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpDb
	 */
	static public function loadDbProcessor() {
		require_once( dirname( __FILE__ ).'/icwp-wpdb.php' );
		return ICWP_APP_WpDb::GetInstance();
	}

	/**
	 * @return ICWP_APP_Ip
	 */
	static public function loadIpProcessor() {
		require_once( dirname( __FILE__ ).'/icwp-ip.php' );
		return ICWP_APP_Ip::GetInstance();
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
		require_once( dirname( __FILE__ ).'/wp-admin-notices.php' );
		return ICWP_APP_WpAdminNotices::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpUsers
	 */
	static public function loadWpUsers() {
		require_once( dirname( __FILE__ ).'/wp-users.php' );
		return ICWP_APP_WpUsers::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpUpgrades
	 */
	static public function loadWpUpgrades() {
		require_once( dirname( __FILE__ ).'/icwp-wpupgrades.php' );
		return ICWP_APP_WpUpgrades::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpComments
	 */
	static public function loadWpCommentsProcessor() {
		require_once( dirname( __FILE__ ).'/wp-comments.php' );
		return ICWP_APP_WpComments::GetInstance();
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