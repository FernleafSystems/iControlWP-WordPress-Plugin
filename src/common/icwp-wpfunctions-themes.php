<?php

if ( !class_exists( 'ICWP_APP_WpFunctions_Themes', false ) ):

	class ICWP_APP_WpFunctions_Themes extends ICWP_APP_Foundation {
		/**
		 * @var ICWP_APP_WpFunctions_Themes
		 */
		protected static $oInstance = NULL;

		private function __construct() {}

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
				require_once( ABSPATH . 'wp-admin/includes/theme.php' );
			}
			return function_exists( 'delete_theme' ) ? delete_theme( $sStylesheet ) : false;
		}

		/**
		 * @param string $sUrlToInstall
		 * @param bool $bOverwrite
		 * @return bool
		 */
		public function install( $sUrlToInstall, $bOverwrite = true ) {
			$aResult = array(
				'successful' => true,
				'plugin_info' => '',
				'errors' => array()
			);

			$oUpgraderSkin = new ICWP_Upgrader_Skin();
			$oUpgrader = new ICWP_Theme_Upgrader( $oUpgraderSkin );
			$oUpgrader->setOverwriteMode( $bOverwrite );
			ob_start();
			$sInstallResult = $oUpgrader->install( $sUrlToInstall );
			ob_end_clean();
			if ( is_wp_error( $oUpgraderSkin->m_aErrors[0] ) ) {
				$aResult['successful'] = false;
				$aResult['errors'] = $oUpgraderSkin->m_aErrors[0]->get_error_messages();
			}
			else {
				$aResult['theme_info'] = $oUpgrader->theme_info();
			}

			$aResult['feedback'] = $oUpgraderSkin->getFeedback();
			return $aResult;
		}

		/**
		 * @return string|WP_Theme
		 */
		public function getCurrentThemeName() {
			return $this->loadWpFunctionsProcessor()->getWordpressIsAtLeastVersion( '3.4.0' )? $this->getCurrent()->get( 'Name' ) : get_current_theme();
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
			if ( $this->loadWpFunctionsProcessor()->getWordpressIsAtLeastVersion( '3.4.0' ) ) {
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
			return $this->loadWpFunctionsProcessor()->getTransient( 'update_themes' );
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
			$oResponse = $this->loadWpFunctionsProcessor()->getTransient( $sKey );
			if ( !is_object( $oResponse ) ) {
				$oResponse = new stdClass();
			}
			$oResponse->last_checked = 0;
			$this->loadWpFunctionsProcessor()->setTransient( $sKey, $oResponse );
		}

		/**
		 * @return array
		 */
		public function wpmsGetSiteAllowedThemes() {
			return ( function_exists( 'get_site_allowed_themes' )? get_site_allowed_themes() : array() );
		}
	}
endif;

if ( !class_exists( 'WP_Upgrader_Skin', false ) ) {
	$sWordPressWpUpgraderClass = ABSPATH.'wp-admin/includes/class-wp-upgrader.php';
	if ( !is_file( $sWordPressWpUpgraderClass ) ) {
		die( '-9999:Failed to find required WP_Upgrader_Skin at '.$sWordPressWpUpgraderClass );
	}
	include_once( $sWordPressWpUpgraderClass );
}

if ( !class_exists( 'ICWP_Upgrader_Skin', false ) && class_exists( 'WP_Upgrader_Skin', false ) ) {

	/**
	 * Class ICWP_Upgrader_Skin
	 */
	class ICWP_Upgrader_Skin extends WP_Upgrader_Skin {

		public $m_aErrors;
		public $aFeedback;

		public function __construct() {
			parent::__construct();
			$this->done_header = true;
		}

		/**
		 * @return array
		 */
		public function getFeedback() {
			return $this->aFeedback;
		}

		function error( $errors ) { }
		function feedback( $string ) { }
	}
}

if ( !class_exists( 'ICWP_Theme_Upgrader', false ) && class_exists( 'Theme_Upgrader' ) ) {

	require_once ABSPATH . 'wp-admin/includes/theme.php'; // to get themes_api
	class ICWP_Theme_Upgrader extends Theme_Upgrader {
		protected $fModeOverwrite = true;

		public function install( $package, $args = array() ) {

			$defaults = array(
				'clear_update_cache' => true,
			);
			$parsed_args = wp_parse_args( $args, $defaults );

			$this->init();
			$this->install_strings();

			add_filter('upgrader_source_selection', array($this, 'check_package') );
			add_filter('upgrader_post_install', array($this, 'check_parent_theme_filter'), 10, 3);

			$this->run( array(
				'package' => $package,
				'destination' => get_theme_root(),
				'clear_destination' => $this->getOverwriteMode(),
				'clear_working' => true,
				'hook_extra' => array(
					'type' => 'theme',
					'action' => 'install',
				),
			) );

			remove_filter('upgrader_source_selection', array($this, 'check_package') );
			remove_filter('upgrader_post_install', array($this, 'check_parent_theme_filter'));

			if ( ! $this->result || is_wp_error($this->result) )
				return $this->result;

			// Refresh the Theme Update information
			wp_clean_themes_cache( $parsed_args['clear_update_cache'] );

			return true;
		}

		public function getOverwriteMode() {
			return $this->fModeOverwrite;
		}

		public function setOverwriteMode( $fOn = true ) {
			$this->fModeOverwrite = $fOn;
		}
	}
}

if ( !class_exists( 'Worpit_Bulk_Theme_Upgrader_Skin', false ) && class_exists( 'Bulk_Theme_Upgrader_Skin', false ) ) {

	/**
	 * Class Worpit_Bulk_Theme_Upgrader_Skin
	 */
	class Worpit_Bulk_Theme_Upgrader_Skin extends Bulk_Theme_Upgrader_Skin {

		/**
		 * @var array
		 */
		public $m_aErrors;

		/**
		 * @var array
		 */
		public $aFeedback;

		/**
		 */
		public function __construct() {
			parent::__construct( compact('title', 'nonce', 'url', 'theme') );
			$this->m_aErrors = array();
			$this->aFeedback = array();
		}

		/**
		 * @param string|array $errors
		 */
		function error( $errors ) {
			$this->m_aErrors[] = $errors;

			if ( is_string( $errors ) ) {
				$this->feedback( $errors );
			}
			else if ( is_wp_error( $errors ) && $errors->get_error_code() ) {
				foreach ( $errors->get_error_messages() as $message ) {
					if ( $errors->get_error_data() ) {
						$this->feedback( $message . ' ' . $errors->get_error_data() );
					}
					else {
						$this->feedback( $message );
					}
				}
			}
		}

		/**
		 * @return array
		 */
		public function getFeedback() {
			return $this->aFeedback;
		}

		/**
		 * @param string $string
		 */
		function feedback( $string ) {
			if ( isset( $this->upgrader->strings[$string] ) )
				$string = $this->upgrader->strings[$string];

			if ( strpos( $string, '%' ) !== false ) {
				$args = func_get_args();
				$args = array_splice( $args, 1 );
				if ( !empty( $args ) ) {
					$string = vsprintf( $string, $args );
				}
			}
			if ( empty( $string ) ) {
				return;
			}
			$this->aFeedback[] = $string;
		}

		function before( $title = '' ) {}
		function after( $title = '' ) {}
		function flush_output() {}
	}
}
