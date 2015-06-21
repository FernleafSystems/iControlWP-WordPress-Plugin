<?php

if ( !class_exists( 'ICWP_APP_WpFunctions_Plugins', false ) ):

	class ICWP_APP_WpFunctions_Plugins {
		/**
		 * @var ICWP_APP_WpFunctions
		 */
		private $oWpFunctions;

		/**
		 * @var ICWP_APP_WpFunctions_Plugins
		 */
		protected static $oInstance = NULL;

		private function __construct() {}

		/**
		 * @param ICWP_APP_WpFunctions $oWpFunctions
		 * @return ICWP_APP_WpFunctions_Plugins
		 */
		public static function GetInstance( $oWpFunctions ) {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
				self::$oInstance->oWpFunctions = $oWpFunctions;
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
			return $this->oWpFunctions->loadWpFilesystem()->deleteDir( $sPath, true );
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
			$oResponse = $this->oWpFunctions->getTransient( $sKey );
			if ( !is_object( $oResponse ) ) {
				$oResponse = new stdClass();
			}
			$oResponse->last_checked = 0;
			$this->oWpFunctions->setTransient( $sKey, $oResponse );
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
			return $this->oWpFunctions->getTransient( 'update_plugins' );
		}
	}
endif;