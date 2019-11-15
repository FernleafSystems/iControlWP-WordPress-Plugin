<?php

class ICWP_APP_WpFunctions_Themes extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpFunctions_Themes
	 */
	protected static $oInstance = null;

	private function __construct() {
	}

	/**
	 * @return ICWP_APP_WpFunctions_Themes
	 */
	public static function GetInstance() {
		if ( is_null( self::$oInstance ) ) {
			self::$oInstance = new self();
		}
		return self::$oInstance;
	}

	/**
	 * @param string $sThemeStylesheet
	 * @return bool
	 */
	public function activate( $sThemeStylesheet ) {
		if ( empty( $sThemeStylesheet ) ) {
			return false;
		}

		$oTheme = $this->getTheme( $sThemeStylesheet );
		if ( !$oTheme->exists() ) {
			return false;
		}

		switch_theme( $oTheme->get_stylesheet() );

		// Now test currently active theme
		$oCurrentTheme = $this->getCurrent();

		return ( !is_null( $oCurrentTheme ) && ( $sThemeStylesheet == $oCurrentTheme->get_stylesheet() ) );
	}

	/**
	 * @param string $sStylesheet
	 * @return bool|WP_Error
	 */
	public function delete( $sStylesheet ) {
		if ( empty( $sStylesheet ) ) {
			return false;
		}
		if ( !function_exists( 'delete_theme' ) ) {
			require_once( ABSPATH.'wp-admin/includes/theme.php' );
		}
		return function_exists( 'delete_theme' ) ? delete_theme( $sStylesheet ) : false;
	}

	/**
	 * @param string $sUrlToInstall
	 * @param bool   $bOverwrite
	 * @return mixed[]
	 */
	public function install( $sUrlToInstall, $bOverwrite = true ) :array {
		$this->loadWpUpgrades();

		$oUpgraderSkin = new ICWP_Upgrader_Skin();
		$oUpgrader = new Theme_Upgrader( $oUpgraderSkin );
		add_filter( 'upgrader_package_options', function ( $aOptions ) use ( $bOverwrite ) {
			$aOptions[ 'clear_destination' ] = $bOverwrite;
			return $aOptions;
		} );

		$mResult = $oUpgrader->install( $sUrlToInstall );

		return [
			'successful' => $mResult === true,
			'feedback'   => $oUpgraderSkin->getIcwpFeedback(),
			'theme_info' => $oUpgrader->theme_info(),
			'errors'     => is_wp_error( $mResult ) ? $mResult->get_error_messages() : [ 'no errors' ]
		];
	}

	/**
	 * @param string $sFile
	 * @return array
	 */
	public function update( $sFile ) {
		$this->loadWpUpgrades();

		$oUpgraderSkin = new ICWP_Upgrader_Skin();
		$oUpgrader = new Theme_Upgrader( $oUpgraderSkin );
		$mResult = $oUpgrader->upgrade( $sFile );

		return [
			'successful' => $mResult === true,
			'feedback'   => $oUpgraderSkin->getIcwpFeedback(),
			'errors'     => is_wp_error( $mResult ) ? $mResult->get_error_messages() : [ 'no errors' ]
		];
	}

	/**
	 * @return string|WP_Theme
	 */
	public function getCurrentThemeName() {
		return $this->loadWP()->getWordpressIsAtLeastVersion( '3.4.0' ) ? $this->getCurrent()
																			   ->get( 'Name' ) : get_current_theme();
	}

	/**
	 * @return null|WP_Theme
	 */
	public function getCurrent() {
		return $this->getTheme();
	}

	/**
	 * @param string $sStylesheet
	 * @return bool
	 */
	public function getExists( $sStylesheet ) {
		$oTheme = $this->getTheme( $sStylesheet );
		return ( !is_null( $oTheme ) && ( $oTheme instanceof WP_Theme ) && $oTheme->exists() );
	}

	/**
	 * @param string $sStylesheet
	 * @return null|WP_Theme
	 */
	public function getTheme( $sStylesheet = null ) {
		if ( $this->loadWP()->getWordpressIsAtLeastVersion( '3.4.0' ) ) {
			if ( !function_exists( 'wp_get_theme' ) ) {
				require_once( ABSPATH.'wp-admin/includes/theme.php' );
			}
			return function_exists( 'wp_get_theme' ) ? wp_get_theme( $sStylesheet ) : null;
		}
		$aThemes = $this->getThemes();
		return array_key_exists( $sStylesheet, $aThemes ) ? $aThemes[ $sStylesheet ] : null;
	}

	/**
	 * Abstracts the WordPress wp_get_themes()
	 * @return array|WP_Theme[]
	 */
	public function getThemes() {
		if ( !function_exists( 'wp_get_themes' ) ) {
			require_once( ABSPATH.'wp-admin/includes/theme.php' );
		}
		return function_exists( 'wp_get_themes' ) ? wp_get_themes() : get_themes();
	}

	/**
	 * @param bool $bForceUpdateCheck
	 * @return stdClass
	 */
	public function getUpdates( $bForceUpdateCheck = false ) {
		if ( $bForceUpdateCheck ) {
			$this->clearUpdates();
			$this->checkForUpdates();
		}
		return $this->loadWP()->getTransient( 'update_themes' );
	}

	/**
	 * @return boolean|null
	 */
	protected function checkForUpdates() {

		if ( class_exists( 'WPRC_Installer' ) && method_exists( 'WPRC_Installer', 'wprc_update_themes' ) ) {
			WPRC_Installer::wprc_update_themes();
			return true;
		}
		else if ( function_exists( 'wp_update_themes' ) ) {
			return ( wp_update_themes() !== false );
		}
		return null;
	}

	/**
	 */
	protected function clearUpdates() {
		$sKey = 'update_themes';
		$oResponse = $this->loadWP()->getTransient( $sKey );
		if ( !is_object( $oResponse ) ) {
			$oResponse = new stdClass();
		}
		$oResponse->last_checked = 0;
		$this->loadWP()->setTransient( $sKey, $oResponse );
	}

	/**
	 * @return array
	 */
	public function wpmsGetSiteAllowedThemes() {
		return ( function_exists( 'get_site_allowed_themes' ) ? get_site_allowed_themes() : [] );
	}
}