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
		public function doActivateTheme( $sThemeStylesheet ) {
			if ( empty( $sThemeStylesheet ) ) {
				return false;
			}

			$oTheme = wp_get_theme( $sThemeStylesheet );
			if ( !$oTheme->exists() ) {
				return false;
			}

			switch_theme( $oTheme->get_stylesheet() );

			// Now test currently active theme
			$oCurrentTheme = $this->getCurrentTheme();

			return ( !is_null( $oCurrentTheme ) && ( $sThemeStylesheet == $oCurrentTheme->get_stylesheet() ) );
		}

		/**
		 * @return string|WP_Theme
		 */
		public function getActiveThemeName() {
			return $this->oWpFunctions->getWordpressIsAtLeastVersion( '3.4.0' )? $this->getCurrentTheme()->get( 'Name' ) : get_current_theme();
		}

		/**
		 * @return null|WP_Theme
		 */
		public function getCurrentTheme() {
			return function_exists( 'wp_get_theme' )? wp_get_theme(): null;
		}

		/**
		 * The method for getting installed themes changed in version 3.4+ so this function normalizes everything.
		 *
		 * @return array
		 */
		public function getInstalledThemes() {

			$aThemes = array();
			$sActiveThemeName = $this->getActiveThemeName();

			if ( $this->oWpFunctions->getWordpressIsAtLeastVersion( '3.4' ) ) {

				/** @var WP_Theme[] $aThemeObjects */
				$aThemeObjects = wp_get_themes();

				foreach ( $aThemeObjects as $oTheme ) {
					$sName = $oTheme->get( 'Name' );
					$aThemes[$sName] = array(
						'Name'				=> $oTheme->display( 'Name' ),
						'Title'				=> $oTheme->offsetGet( 'Title' ),
						'Description'		=> $oTheme->offsetGet( 'Description' ),
						'Author'			=> $oTheme->offsetGet( 'Author' ),
						'Author Name'		=> $oTheme->offsetGet( 'Author Name' ),
						'Author URI'		=> $oTheme->offsetGet( 'Author URI' ),
						'Version'			=> $oTheme->offsetGet( 'Version' ),

						'Template'			=> $oTheme->offsetGet( 'Template' ),
						'Stylesheet'		=> $oTheme->offsetGet( 'Stylesheet' ),
						//'Template Dir'		=> $oTheme->offsetGet( 'Template Dir' ),
						//'Stylesheet Dir'	=> $oTheme->offsetGet( 'Stylesheet Dir' ),
						'Theme Root'		=> $oTheme->offsetGet( 'Theme Root' ),
						'Theme Root URI'	=> $oTheme->offsetGet( 'Theme Root URI' ),

						'Status'			=> $oTheme->offsetGet( 'Status' ),

						// We add our own
						'active'			=> $sActiveThemeName == $sName? 1: 0,
						'network_active'	=> $oTheme->is_allowed( 'network' )
					);
				}
			}
			else {
				$aThemes = get_themes();
				$fIsMultisite = is_multisite();
				$aNetworkAllowedThemes = $this->wpmsGetSiteAllowedThemes();

				// We add our own here because it's easier due to WordPress differences
				foreach( $aThemes as $sName => $aData ) {
					$aThemes[$sName]['active'] = $sActiveThemeName == $aData['Name']? 1: 0;
					$aThemes[$sName]['network_active'] = $fIsMultisite && isset( $aNetworkAllowedThemes[ $aData['Stylesheet'] ] );
				}
			}

			return $aThemes;
		}

		/**
		 * Abstracts the WordPress get_themes()
		 * @return array|WP_Theme[]
		 */
		public function getThemes() {
			return function_exists( 'wp_get_themes' )? wp_get_themes(): get_themes();
		}

		/**
		 * @return array
		 */
		public function wpmsGetSiteAllowedThemes() {
			return ( function_exists( 'get_site_allowed_themes' )? get_site_allowed_themes() : array() );
		}
	}
endif;