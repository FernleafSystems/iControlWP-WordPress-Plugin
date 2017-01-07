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
		 * @param string $sThemeFile				    (optional)
		 * @param boolean $bForceUpdateCheck		    (optional)
		 * @return array[]								associative: ThemeStylesheet => ThemeData
		 */
		public function collectWordpressThemes( $sThemeFile = null, $bForceUpdateCheck = false ) {

			$oWpThemes = $this->loadWpFunctionsThemes();

//			$this->prepThirdPartyThemes(); //TODO
			$aThemes = empty( $sThemeFile ) ? $oWpThemes->getThemes() : array( $sThemeFile => $oWpThemes->getTheme( $sThemeFile ) );
			$aThemes = $this->normaliseThemeData( $aThemes );

			$oCurrentUpdates = $oWpThemes->getUpdates( $bForceUpdateCheck );
			$aAutoUpdatesList = $this->getAutoUpdates( 'themes' );

			$bIsMultisite = is_multisite();
			$aNetworkAllowedThemes = $this->loadWpFunctionsThemes()->wpmsGetSiteAllowedThemes();

			$oActiveTheme = $this->loadWpFunctionsThemes()->getCurrent();
			$sActiveThemeStylesheet = $oActiveTheme->get_stylesheet();

			foreach ( $aThemes as $nIndex => $aData ) {

				$aThemes[ $nIndex ][ 'active' ] = ( $sActiveThemeStylesheet == $aData[ 'Stylesheet' ] ) ? 1 : 0;
				if ( !isset( $aData['network_active'] ) ) {
					$aThemes[ $nIndex ][ 'network_active' ] = ( $bIsMultisite && isset( $aNetworkAllowedThemes[ $aData[ 'Stylesheet' ] ] ) );
				}

				// is it set to autoupdate ?
				$aThemes[ $nIndex ][ 'auto_update' ] = in_array( $aData[ 'Stylesheet' ], $aAutoUpdatesList );

				$aThemes[$nIndex]['update_available']	= isset( $oCurrentUpdates->response[$aData['Stylesheet']] ) ? 1: 0;

				$aThemes[ $nIndex ][ 'update_info' ] = '';
				if ( $aThemes[$nIndex]['update_available'] ) {
					$aThemes[ $nIndex ][ 'update_info' ] = json_encode( $oCurrentUpdates->response[ $aData[ 'Stylesheet' ] ] );
				}
			}
			return $aThemes;
		}

		/**
		 * @param array $aThemes
		 * @return array[]
		 */
		protected function normaliseThemeData( $aThemes ) {

			$aNormalizedThemes = array();

			if ( $this->loadWpFunctionsProcessor()->getWordpressIsAtLeastVersion( '3.4' ) ) {

				/** @var WP_Theme[] $aThemes */
				foreach ( $aThemes as $sStylesheet => $oTheme ) {
					$aNormalizedThemes[ $sStylesheet ] = array(
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

						// We add our own data here because it's easier while it's an object
						'network_active'	=> $oTheme->is_allowed( 'network' )
					);
				}
			}
			else {
				$aNormalizedThemes = $aThemes;
			}

			return $aNormalizedThemes;
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