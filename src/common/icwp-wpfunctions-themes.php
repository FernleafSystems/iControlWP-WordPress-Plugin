<?php

if ( !class_exists( 'ICWP_APP_WpFunctions_Themes', false ) ):

	class ICWP_APP_WpFunctions_Themes {
		/**
		 * @var ICWP_APP_WpFunctions
		 */
		private $oWpFunctions;

		/**
		 * @var ICWP_APP_WpFunctions_Themes
		 */
		protected static $oInstance = NULL;

		private function __construct() {}

		/**
		 * @param ICWP_APP_WpFunctions $oWpFunctions
		 * @return ICWP_APP_WpFunctions_Themes
		 */
		public static function GetInstance( $oWpFunctions ) {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
				self::$oInstance->oWpFunctions = $oWpFunctions;
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
			$oCurrentTheme = $this->getActiveTheme();

			return ( !is_null( $oCurrentTheme ) && ( $sThemeStylesheet == $oCurrentTheme->get_stylesheet() ) );
		}

		/**
		 * @param string $sStylesheet
		 * @return bool|void|WP_Error
		 */
		public function delete( $sStylesheet ) {
			if ( empty( $sStylesheet ) ) {
				return false;
			}

			if ( !$this->getExists( $sStylesheet ) ) {
				return false;
			}

			$oThemeToDelete = $this->getTheme( $sStylesheet );
			if ( $oThemeToDelete->get_stylesheet_directory() == get_stylesheet_directory() ) {
				return false;
			}

			return delete_theme( $sStylesheet );
		}

		/**
		 * @return string|WP_Theme
		 */
		public function getActiveThemeName() {
			return $this->oWpFunctions->getWordpressIsAtLeastVersion( '3.4.0' )? $this->getActiveTheme()->get( 'Name' ) : get_current_theme();
		}

		/**
		 * @return null|WP_Theme
		 */
		public function getActiveTheme() {
			return $this->getTheme( get_stylesheet() );
		}

		/**
		 * @param $sStylesheet
		 * @return null|WP_Theme
		 */
		public function getExists( $sStylesheet ) {
			$oTheme = $this->getTheme( $sStylesheet );
			return ( !is_null( $oTheme ) && ( $oTheme instanceof WP_Theme ) && $oTheme->exists() );
		}

		/**
		 * @param string $sStylesheet
		 * @return null|WP_Theme
		 */
		public function getTheme( $sStylesheet ) {
			if ( $this->oWpFunctions->getWordpressIsAtLeastVersion( '3.4.0' ) ) {
				if ( !function_exists( 'wp_get_theme' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/theme.php' );
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
				require_once( ABSPATH . 'wp-admin/includes/theme.php' );
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
			return $this->oWpFunctions->getTransient( 'update_themes' );
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
				return (wp_update_themes() !== false);
			}
			return null;
		}

		/**
		 * @return boolean|null
		 */
		protected function clearUpdates() {
			$sKey = 'update_themes';
			$oResponse = $this->oWpFunctions->getTransient( $sKey );
			if ( !is_object( $oResponse ) ) {
				$oResponse = new stdClass();
			}
			$oResponse->last_checked = 0;
			$this->oWpFunctions->setTransient( $sKey, $oResponse );
		}

		/**
		 * @return array
		 */
		public function wpmsGetSiteAllowedThemes() {
			return ( function_exists( 'get_site_allowed_themes' )? get_site_allowed_themes() : array() );
		}
	}
endif;