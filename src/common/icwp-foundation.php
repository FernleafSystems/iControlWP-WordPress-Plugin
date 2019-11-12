<?php

class ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_Render
	 */
	private static $oRender;

	/**
	 * @var ICWP_APP_WpComments
	 */
	private static $oWpComments;

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
	static public function loadDP() {
		return ICWP_APP_DataProcessor::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFilesystem
	 */
	static public function loadFS() {
		return ICWP_APP_WpFilesystem::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFunctions
	 */
	static public function loadWP() {
		return ICWP_APP_WpFunctions::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpCron
	 */
	static public function loadWpCronProcessor() {
		return ICWP_APP_WpCron::GetInstance();
	}

	/**
	 * @return void
	 */
	static public function loadWpWidgets() {
	}

	/**
	 * @return ICWP_APP_WpFunctions_Plugins
	 */
	public function loadWpFunctionsPlugins() {
		return ICWP_APP_WpFunctions_Plugins::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpFunctions_Themes
	 */
	public function loadWpFunctionsThemes() {
		return ICWP_APP_WpFunctions_Themes::GetInstance();
	}

	/**
	 * @return ICWP_APP_Encrypt
	 */
	public function loadEncryptProcessor() {
		return ICWP_APP_Encrypt::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpDb
	 */
	static public function loadDbProcessor() {
		return ICWP_APP_WpDb::GetInstance();
	}

	/**
	 * @return ICWP_APP_Ip
	 */
	static public function loadIpProcessor() {
		return ICWP_APP_Ip::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpTrack
	 * @deprecated 3.7
	 */
	static public function loadWpTrack() {
		if ( !isset( self::$oTrack ) ) {
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
		return ICWP_APP_WpAdminNotices::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpUsers
	 */
	static public function loadWpUsers() {
		return ICWP_APP_WpUsers::GetInstance();
	}

	/**
	 * @return ICWP_APP_WpUpgrades
	 */
	static public function loadWpUpgrades() {
		if ( !isset( self::$oUpgrades ) ) {
			self::$oUpgrades = ICWP_APP_WpUpgrades::GetInstance();
		}

		return self::$oUpgrades;
	}

	/**
	 * @return ICWP_APP_WpComments
	 */
	static public function loadWpCommentsProcessor() {
		if ( !isset( self::$oWpComments ) ) {
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