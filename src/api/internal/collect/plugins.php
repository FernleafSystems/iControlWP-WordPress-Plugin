<?php

class ICWP_APP_Api_Internal_Collect_Plugins extends ICWP_APP_Api_Internal_Collect_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		return $this->success( array( 'wordpress-plugins' => $this->collect() ) );
	}

	/**
	 * @see class-wp-plugins-list-table.php
	 * @see plugins.php
	 * @return array                                associative: PluginFile => PluginData
	 */
	public function collect() {

//			$this->prepThirdPartyPlugins(); TODO
		$aPlugins = $this->getInstalledPlugins( $this->getDesiredPluginAttributes() );

		$oUpdates = $this->loadWpFunctions()
						 ->updatesGather( 'plugins', $this->isForceUpdateCheck() ); // option to do another update check? force it?

		$aAutoUpdates = $this->getAutoUpdates( 'plugins' );
		$sServicePluginBaseFile = ICWP_Plugin::getController()->getPluginBaseFile();

		foreach ( $aPlugins as $sFile => &$aData ) {
			$aData[ 'active' ] = is_plugin_active( $sFile );
			$aData[ 'auto_update' ] = in_array( $sFile, $aAutoUpdates );
			$aData[ 'file' ] = $sFile;
			$aData[ 'is_service_plugin' ] = ( $sFile == $sServicePluginBaseFile );
			$aData[ 'network_active' ] = is_plugin_active_for_network( $sFile );
			$aData[ 'update_available' ] = isset( $oUpdates->response[ $sFile ] ) ? 1 : 0;
			$aData[ 'update_info' ] = '';

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
			if ( empty( $aData[ 'slug' ] ) && !empty( $oUpdates->no_update[ $sFile ]->slug ) ) {
				$aData[ 'slug' ] = $oUpdates->no_update[ $sFile ]->slug;
			}
		}
		return $aPlugins;
	}

	/**
	 * Gets all the installed plugin and filters
	 * out unnecessary information based on "desired attributes"
	 * @param array $aDesiredAttributes
	 * @return array
	 */
	protected function getInstalledPlugins( $aDesiredAttributes = null ) {
		$aPlugins = $this->loadWpFunctionsPlugins()->getPlugins();
		if ( !empty( $aDesiredAttributes ) ) {
			foreach ( $aPlugins as $sPluginFile => $aData ) {
				$aPlugins[ $sPluginFile ] = array_intersect_key( $aData, array_flip( $aDesiredAttributes ) );
			}
		}
		return $aPlugins;
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
}