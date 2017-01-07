<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Base', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Base extends ICWP_APP_Api_Internal_Base {

		/**
		 * @see class-wp-plugins-list-table.php
		 * @see plugins.php
		 *
		 * @param boolean $fForceUpdateCheck			(optional)
		 * @return array								associative: PluginFile => PluginData
		 */
		protected function collectWordpressPlugins( $fForceUpdateCheck = false ) {

//			$this->prepThirdPartyPlugins();
			$aPlugins = $this->getInstalledPlugins();
			$oUpdates = $this->loadWpFunctionsProcessor()->updatesGather( 'plugins', $fForceUpdateCheck ); // option to do another update check? force it?
			$aAutoUpdates = $this->getAutoUpdates( 'plugins' );
			$sServicePluginBaseFile = ICWP_Plugin::getController()->getPluginBaseFile();

			foreach ( $aPlugins as $sFile => &$aData ) {
				$aData[ 'active' ]				= is_plugin_active( $sFile );
				$aData[ 'network_active' ]		= is_plugin_active_for_network( $sFile );
				$aData[ 'file' ]				= $sFile;
				$aData[ 'is_service_plugin' ]	= ( $sFile == $sServicePluginBaseFile );
				$aData[ 'auto_update' ]			= in_array( $sFile, $aAutoUpdates );

				$aData[ 'update_available' ]	= isset( $oUpdates->response[$sFile] )? 1: 0;
				$aData[ 'update_info' ]			= '';

				if ( $aData[ 'update_available' ] ) {
					$oUpdateInfo = $oUpdates->response[ $sFile ];
					if ( isset( $oUpdateInfo->sections ) ) {
						unset( $oUpdateInfo->sections );
					}
					if ( isset( $oUpdateInfo->changelog ) ) {
						unset( $oUpdateInfo->changelog );
					}

					$aData[ 'update_info' ] = json_encode( $oUpdateInfo );
					if ( !empty( $oUpdateInfo->slug ) ) {
						$aData[ 'slug' ] = $oUpdateInfo->slug;
					}
				}

				// $oCurrentUpdates->no_update seems to be relatively new
				if ( empty( $aData['slug'] && !empty( $oUpdates->no_update[$sFile]->slug ) ) ) {
					$aData[ 'slug' ] = $oUpdates->no_update[$sFile]->slug;
				}
			}
			return $aPlugins;
		}

		/**
		 * @param boolean $fForceUpdateCheck		    (optional)
		 * @return array								associative: ThemeStylesheet => ThemeData
		 */
		public function collectWordpressThemes( $fForceUpdateCheck = false ) {

//			$this->prepThirdPartyThemes();
			$aThemes = $this->getInstalledThemes();
			$oUpdates = $this->loadWpFunctionsProcessor()->updatesGather( 'themes', $fForceUpdateCheck ); // option to do another update check? force it?
			$aAutoUpdates = $this->getAutoUpdates( 'themes' );

			$sActiveThemeName = $this->loadWpFunctionsThemes()->getCurrentThemeName();

			foreach ( $aThemes as $sName => &$aData ) {
				$aData[ 'active' ]				= ( $sName == $sActiveThemeName );
				$aData[ 'auto_update' ]			= in_array( $sName, $aAutoUpdates );

				$aData['update_available']	= isset( $oUpdates->response[$aData['Stylesheet']] )? 1: 0;
				$aData['update_info']		= '';

				if ( $aData['update_available'] ) {
					$oUpdateInfo = $oUpdates->response[ $aData[ 'Stylesheet' ] ];

					if ( isset( $oUpdateInfo['sections'] ) ) {
						unset( $oUpdateInfo['sections'] ); // TODO: Filter unwanted data using set array of keys
					}
					$aData['update_info'] = json_encode( $oUpdateInfo );
				}
			}
			return $aThemes;
		}

		/**
		 * Gets all the installed plugin and filters
		 * out unnecessary information based on "desired attributes"
		 *
		 * @return array
		 */
		protected function getInstalledPlugins() {
			$aPlugins = $this->loadWpFunctionsPlugins()->getPlugins();
			foreach ( $aPlugins as $sPluginFile => $aData ) {
				$aPlugins[ $sPluginFile ] = array_intersect_key( $aData, array_flip( $this->getDesiredPluginAttributes() ) );
			}
			return $aPlugins;
		}

		/**
		 * The method for getting installed themes changed in version 3.4+ so this function normalizes everything.
		 *
		 * @return array
		 */
		public function getInstalledThemes() {

			$aThemes = array();

			if ( $this->loadWpFunctionsProcessor()->getWordpressIsAtLeastVersion( '3.4' ) ) {

				/** @var WP_Theme[] $aThemeObjects */
				$aThemeObjects = $this->loadWpFunctionsThemes()->getThemes();

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
						'network_active'	=> $oTheme->is_allowed( 'network' )
					);
					$aThemes[$sName] = array_intersect_key( $aThemes[$sName], array_flip( $this->getDesiredThemeAttributes() ) );
				}
			}
			else {
				$aThemes = $this->loadWpFunctionsThemes()->getThemes();
				$fIsMultisite = is_multisite();
				$aNetworkAllowedThemes = function_exists( 'get_site_allowed_themes' )? get_site_allowed_themes() : array();

				// We add our own here because it's easier due to WordPress differences
				foreach( $aThemes as $sName => $aData ) {
					$aThemes[$sName]['network_active'] = $fIsMultisite && isset( $aNetworkAllowedThemes[ $aData['Stylesheet'] ] );
				}
			}

			return $aThemes;
		}

		/**
		 * @param string $sContext
		 * @return mixed
		 */
		protected function getAutoUpdates( $sContext = 'plugins' ) {
			return ICWP_Plugin::GetAutoUpdatesSystem()->getAutoUpdates( $sContext );
		}

		/**
		 * Manual preparation for third party plugin update checking that hook into 'init' so we can't "grab" them
		 */
		public function prepThirdPartyPlugins() {
			//Headway Blocks
			$this->doHeadwayBlocks();
			//Soliloquy Slider
			$this->doSoliloquy();
			//WP Migrate DB Pro
			$this->doWpMigrateDbPro();
			//White Label Branding
			$this->doWhiteLabelBranding();
			$this->doMisc();
			//Yoast SEO Plugin
			$this->doYoastSeo();
			//Advanced Custom Fields Pro Plugin
			$this->doAdvancedCustomFieldsPro();
			//Handle Backup Buddy
			$this->doIThemes();
		}

		/**
		 * @return array
		 */
		protected function getDesiredPluginAttributes() {
			return array(
				'Name',
				'PluginURI',
				'Version',
				'Network',
				'slug',
				'Version'
			);
		}

		/**
		 * @return array
		 */
		protected function getDesiredThemeAttributes() {
			return array(
				'Name',
				'Version',
				'Template',
				'Stylesheet',
				'Network',
				'active',
				'network_active'
			);
		}
	}

endif;