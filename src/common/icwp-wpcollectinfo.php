<?php

if ( !class_exists( 'ICWP_APP_WpCollectInfo', false ) ):

	class ICWP_APP_WpCollectInfo extends ICWP_APP_Foundation {

		/**
		 * @var ICWP_APP_WpCollectInfo
		 */
		protected static $oInstance = NULL;

		/**
		 * @return ICWP_APP_WpCollectInfo
		 */
		public static function GetInstance() {
			if ( is_null( self::$oInstance ) ) {
				self::$oInstance = new self();
			}
			return self::$oInstance;
		}

		public function __construct() {}

		/**
		 * @see class-wp-plugins-list-table.php
		 * @see plugins.php
		 *
		 * @param string $sPluginFile					if null, collect all plugins
		 * @param boolean $bForceUpdateCheck			(optional)
		 * @return array[]								associative: PluginFile => PluginData
		 */
		public function collectWordpressPlugins( $sPluginFile = null, $bForceUpdateCheck = false ) {

			$oWpPlugins = $this->loadWpFunctionsPlugins();

//			$this->prepThirdPartyPlugins(); //TODO

			$aPlugins = empty( $sPluginFile ) ? $oWpPlugins->getPlugins() : array( $sPluginFile => $oWpPlugins->getPlugin( $sPluginFile ) );
			$oCurrentUpdates = $oWpPlugins->getUpdates( $bForceUpdateCheck );
			$aAutoUpdatesList = $this->getAutoUpdates( 'plugins' );

			foreach ( $aPlugins as $sPluginFile => $aData ) {

				$aPlugins[$sPluginFile]['file'] = $sPluginFile;

				// is it active ?
				$aPlugins[$sPluginFile]['active']			= is_plugin_active( $sPluginFile );
				$aPlugins[$sPluginFile]['network_active']	= is_plugin_active_for_network( $sPluginFile );

				// is it set to autoupdate ?
				$aPlugins[$sPluginFile]['auto_update'] = in_array( $sPluginFile, $aAutoUpdatesList );

				// is there an update ?
				$aPlugins[$sPluginFile]['update_available']	= isset( $oCurrentUpdates->response[$sPluginFile] )? 1: 0;

				$aPlugins[$sPluginFile]['update_info']		= '';
				if ( $aPlugins[$sPluginFile]['update_available'] ) {
					$aPlugins[$sPluginFile]['update_info'] = json_encode( $oCurrentUpdates->response[$sPluginFile] );
				}
			}

			$sServicePluginBaseFile = ICWP_Plugin::getController()->getPluginBaseFile();
			if ( isset( $aPlugins[ $sServicePluginBaseFile ] ) ) {
				$aPlugins[ $sServicePluginBaseFile ][ 'is_service_plugin' ] = 1;
			}

			return $aPlugins;
		}

		/**
		 * @param string $sContext
		 * @return mixed
		 */
		protected function getAutoUpdates( $sContext = 'plugins' ) {
			$oAutoupdatesSystem = ICWP_Plugin::GetAutoUpdatesSystem();
			return $oAutoupdatesSystem->getAutoUpdates( $sContext );
		}

	}
endif;