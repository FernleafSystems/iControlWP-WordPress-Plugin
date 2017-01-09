<?php

if ( !class_exists( 'ICWP_APP_WpFunctions_Plugins', false ) ):

	class ICWP_APP_WpFunctions_Plugins extends ICWP_APP_Foundation {

		/**
		 * @var ICWP_APP_WpFunctions_Plugins
		 */
		protected static $oInstance = NULL;

		private function __construct() {}

		/**
		 * @return ICWP_APP_WpFunctions_Plugins
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}

		/**
		 * @param string $sPluginFile
		 * @param bool $bNetworkWide
		 * @return null|WP_Error
		 */
		public function activate( $sPluginFile, $bNetworkWide = false ) {
			return activate_plugin( $sPluginFile, '', $bNetworkWide );
		}

		/**
		 * @param string $sPluginFile
		 * @param bool $bNetworkWide
		 */
		public function deactivate( $sPluginFile, $bNetworkWide = false ) {
			deactivate_plugins( $sPluginFile, '', $bNetworkWide );
		}

		/**
		 * @param string $sPluginFile
		 * @param bool $bNetworkWide
		 * @return bool
		 */
		public function delete( $sPluginFile, $bNetworkWide = false ) {
			if ( empty( $sPluginFile ) || !$this->getIsInstalled( $sPluginFile ) ) {
				return false;
			}

			if ( $this->getIsActive( $sPluginFile ) ) {
				$this->deactivate( $sPluginFile, $bNetworkWide );
			}
			$this->uninstall( $sPluginFile );

			// delete the folder
			$sPluginDir = dirname( $sPluginFile );
			if ( $sPluginDir == '.' ) { //it's not within a sub-folder
				$sPluginDir = $sPluginFile;
			}
			$sPath = path_join( WP_PLUGIN_DIR, $sPluginDir );
			return $this->loadFileSystemProcessor()->deleteDir( $sPath );
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
			$oUpgrader = new ICWP_Plugin_Upgrader( $oUpgraderSkin );
			$oUpgrader->setOverwriteMode( $bOverwrite );
			ob_start();
			$sInstallResult = $oUpgrader->install( $sUrlToInstall );
			ob_end_clean();
			if ( is_wp_error( $oUpgraderSkin->m_aErrors[0] ) ) {
				$aResult['successful'] = false;
				$aResult['errors'] = $oUpgraderSkin->m_aErrors[0]->get_error_messages();
			}
			else {
				$aResult['plugin_info'] = $oUpgrader->plugin_info();
			}

			$aResult['feedback'] = $oUpgraderSkin->getFeedback();

			return $aResult;
		}

		/**
		 * @param string $sFile
		 * @return array
		 */
		public function update( $sFile ) {
			$aResult = array(
				'successful' => 1,
				'errors' => array()
			);

			$oUpgraderSkin = new ICWP_Bulk_Plugin_Upgrader_Skin();
			$oUpgrader = new Plugin_Upgrader( $oUpgraderSkin );
			ob_start();
			$oUpgrader->bulk_upgrade( array( $sFile ) );
			ob_end_clean();

			if ( isset( $oUpgraderSkin->m_aErrors[0] ) ) {
				if ( is_wp_error( $oUpgraderSkin->m_aErrors[0] ) ) {
					$aResult['successful'] = 0;
					$aResult['errors'] = $oUpgraderSkin->m_aErrors[0]->get_error_messages();
				}
			}
			$aResult['feedback'] = $oUpgraderSkin->getFeedback();
			return $aResult;
		}

		/**
		 * @param string $sPluginFile
		 * @return true
		 */
		public function uninstall( $sPluginFile ) {
			return uninstall_plugin( $sPluginFile );
		}

		/**
		 * @return boolean|null
		 */
		protected function checkForUpdates() {

			if ( class_exists( 'WPRC_Installer' ) && method_exists( 'WPRC_Installer', 'wprc_update_plugins' ) ) {
				WPRC_Installer::wprc_update_plugins();
				return true;
			}
			else if ( function_exists( 'wp_update_plugins' ) ) {
				return ( wp_update_plugins() !== false );
			}
			return null;
		}

		/**
		 * @return boolean|null
		 */
		protected function clearUpdates() {
			$sKey = 'update_plugins';
			$oResponse = $this->loadWpFunctionsProcessor()->getTransient( $sKey );
			if ( !is_object( $oResponse ) ) {
				$oResponse = new stdClass();
			}
			$oResponse->last_checked = 0;
			$this->loadWpFunctionsProcessor()->setTransient( $sKey, $oResponse );
		}

		/**
		 * @param string $sPluginFile
		 * @return bool
		 */
		public function getIsActive( $sPluginFile ) {
			return $this->getIsInstalled( $sPluginFile ) ? is_plugin_active( $sPluginFile ) : false;
		}

		/**
		 * @param string $sPluginFile
		 * @return bool
		 */
		public function getIsInstalled( $sPluginFile ) {
			$aPlugins = $this->getPlugins();
			if ( empty( $aPlugins ) || !is_array( $aPlugins ) ) {
				return false;
			}
			return array_key_exists( $sPluginFile, $aPlugins );
		}

		/**
		 * @param string $sPluginFile
		 * @return array|null
		 */
		public function getPlugin( $sPluginFile ) {
			$aPlugins = $this->getPlugins();
			return array_key_exists( $sPluginFile, $aPlugins ) ? $aPlugins[ $sPluginFile ] : null;
		}

		/**
		 * @return array[]
		 */
		public function getPlugins() {
			if ( !function_exists( 'get_plugins' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			}
			return function_exists( 'get_plugins' ) ? get_plugins() : array();
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
			return $this->loadWpFunctionsProcessor()->getTransient( 'update_plugins' );
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

if ( !class_exists( 'ICWP_Plugin_Upgrader', false ) && class_exists( 'Plugin_Upgrader' ) ) {
	class ICWP_Plugin_Upgrader extends Plugin_Upgrader {
		protected $fModeOverwrite = true;

		public function install( $package, $args = array() ) {

			$defaults = array(
				'clear_update_cache' => true,
			);
			$parsed_args = wp_parse_args( $args, $defaults );

			$this->init();
			$this->install_strings();

			add_filter('upgrader_source_selection', array($this, 'check_package') );

			$this->run( array(
				'package' => $package,
				'destination' => WP_PLUGIN_DIR,
				'clear_destination' => $this->getOverwriteMode(), // this is the key to overwrite and why we're extending the native wordpress class
				'clear_working' => true,
				'hook_extra' => array(
					'type' => 'plugin',
					'action' => 'install',
				)
			) );

			remove_filter('upgrader_source_selection', array($this, 'check_package') );

			if ( ! $this->result || is_wp_error($this->result) )
				return $this->result;

			// Force refresh of plugin update information
			wp_clean_plugins_cache( $parsed_args['clear_update_cache'] );

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

if ( !class_exists( 'ICWP_Bulk_Plugin_Upgrader_Skin', false ) && class_exists( 'Bulk_Plugin_Upgrader_Skin', false ) ) {
	/**
	 * Class ICWP_Bulk_Plugin_Upgrader_Skin
	 */
	class ICWP_Bulk_Plugin_Upgrader_Skin extends Bulk_Plugin_Upgrader_Skin {

		/**
		 * @var array
		 */
		public $m_aErrors;

		/**
		 * @var array
		 */
		public $aFeedback;

		/**
		 *
		 */
		public function __construct() {
			parent::__construct( compact( 'nonce', 'url' ) );
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

		/*
		function footer() {
			var_dump(debug_backtrace());
			die( 'testing' );
		}
		*/
	}
}