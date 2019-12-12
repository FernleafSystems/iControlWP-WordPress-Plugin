<?php

class ICWP_APP_WpFunctions_Plugins extends ICWP_APP_Foundation {

	/**
	 * @var ICWP_APP_WpFunctions_Plugins
	 */
	protected static $oInstance = null;

	private function __construct() {
	}

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
	 * @param bool   $bNetworkWide
	 * @return null|WP_Error
	 */
	public function activate( $sPluginFile, $bNetworkWide = false ) {
		return activate_plugin( $sPluginFile, '', $bNetworkWide );
	}

	/**
	 * @param string $sPluginFile
	 * @param bool   $bNetworkWide
	 */
	public function deactivate( $sPluginFile, $bNetworkWide = false ) {
		deactivate_plugins( $sPluginFile, '', $bNetworkWide );
	}

	/**
	 * @param string $sPluginFile
	 * @param bool   $bNetworkWide
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
		return $this->loadFS()->deleteDir( $sPath );
	}

	/**
	 * @param string $sUrlToInstall
	 * @param bool   $bOverwrite
	 * @return mixed[]
	 */
	public function install( $sUrlToInstall, $bOverwrite = true ) {
		$this->loadWpUpgrades();

		$oSkin = $this->loadWP()->getWordpressIsAtLeastVersion( '5.3' ) ?
			new \ICWP_Upgrader_Skin()
			: new \ICWP_Upgrader_Skin_Legacy();
		$oUpgrader = new Plugin_Upgrader( $oSkin );
		add_filter( 'upgrader_package_options', function ( $aOptions ) use ( $bOverwrite ) {
			$aOptions[ 'clear_destination' ] = $bOverwrite;
			return $aOptions;
		} );

		$mResult = $oUpgrader->install( $sUrlToInstall );

		return [
			'successful'  => $mResult === true,
			'feedback'    => method_exists( $oSkin, 'getIcwpFeedback' ) ? $oSkin->getIcwpFeedback() : [],
			'plugin_info' => $oUpgrader->plugin_info(),
			'errors'      => is_wp_error( $mResult ) ? $mResult->get_error_messages() : [ 'no errors' ]
		];
	}

	/**
	 * @param string $sFile
	 * @return mixed[]
	 */
	public function update( $sFile ) {
		$this->loadWpUpgrades();

		$oSkin = $this->loadWP()->getWordpressIsAtLeastVersion( '5.3' ) ?
			new \ICWP_Upgrader_Skin()
			: new \ICWP_Upgrader_Skin_Legacy();
		$oUpgrader = new Plugin_Upgrader( $oSkin );
		$mResult = $oUpgrader->bulk_upgrade( [ $sFile ] );

		$aErrors = [];
		/** @var array|\WP_Error $mDetails */
		$mDetails = ( is_array( $mResult ) && isset( $mResult[ $sFile ] ) ) ? $mResult[ $sFile ] : [];
		if ( empty( $mDetails ) ) {
			$aErrors[] = 'False - Filesystem Error';
		}
		elseif ( is_wp_error( $mDetails ) ) {
			$mDetails = [];
			$aErrors = $mDetails->get_error_messages();
		}

		return [
			'successful' => !empty( $aDetails ),
			'errors'     => $aErrors,
			'details'    => $mDetails,
			'feedback'   => method_exists( $oSkin, 'getIcwpFeedback' ) ? $oSkin->getIcwpFeedback() : [],
		];
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
		elseif ( function_exists( 'wp_update_plugins' ) ) {
			return ( wp_update_plugins() !== false );
		}
		return null;
	}

	/**
	 */
	protected function clearUpdates() {
		$sKey = 'update_plugins';
		$oResponse = $this->loadWP()->getTransient( $sKey );
		if ( !is_object( $oResponse ) ) {
			$oResponse = new stdClass();
		}
		$oResponse->last_checked = 0;
		$this->loadWP()->setTransient( $sKey, $oResponse );
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
			require_once( ABSPATH.'wp-admin/includes/plugin.php' );
		}
		return function_exists( 'get_plugins' ) ? get_plugins() : [];
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
		return $this->loadWP()->getTransient( 'update_plugins' );
	}
}