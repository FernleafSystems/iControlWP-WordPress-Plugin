<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api_Internal', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'plugin_api.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api_Internal
	 */
	class ICWP_APP_Processor_Plugin_Api_Internal extends ICWP_APP_Processor_Plugin_Api {

		const ApiMethodPrefix = 'icwpapi_';

		/**
		 * @return stdClass
		 */
		protected function processAction() {
			$sActionName = $this->getCurrentApiActionName();

			if ( !$this->isActionSupported( $sActionName ) ) {
				return $this->setErrorResponse(
					sprintf( 'Action "%s" is not currently supported.', $sActionName )
					-1 //TODO: Set a code
				);
			}

			if ( !$this->isActionDefined( $sActionName ) ) {
				return $this->setErrorResponse(
					sprintf( 'Action "%s" is not currently defined.', $sActionName )
					-1 //TODO: Set a code
				);
			}

			return call_user_func( array( $this, self::ApiMethodPrefix.$sActionName ) );
		}

		/**
		 * @return stdClass
		 */
		protected function icwpapi_plugin_activate() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$sPluginFile = $oFO->fetchIcwpRequestParam( 'plugin_file', '', true );
			$bIsWpms = $oFO->fetchIcwpRequestParam( 'site_is_wpms', '', true );

			$bResult = $this->loadWpFunctionsPlugins()->activate( $sPluginFile, $bIsWpms );
			$aPlugin = $this->getWpCollector()->collectWordpressPlugins( $sPluginFile );
			$aData = array(
				'result'			=> $bResult,
				'single-plugin'		=> $aPlugin[ $sPluginFile ]
			);
			return $this->success( $aData );
		}

		/**
		 * @return stdClass
		 */
		protected function icwpapi_plugin_deactivate() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$sPluginFile = $oFO->fetchIcwpRequestParam( 'plugin_file', '', true );
			$bIsWpms = $oFO->fetchIcwpRequestParam( 'site_is_wpms', '', true );

			$this->loadWpFunctionsPlugins()->deactivate( $sPluginFile, $bIsWpms );
			$aPlugin = $this->getWpCollector()->collectWordpressPlugins( $sPluginFile );
			$aData = array(
				'result'			=> true,
				'single-plugin'		=> $aPlugin[ $sPluginFile ]
			);
			return $this->success( $aData );
		}

		/**
		 * @return stdClass
		 */
		protected function icwpapi_plugin_delete() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$sPluginFile = $oFO->fetchIcwpRequestParam( 'plugin_file', '', true );
			$bIsWpms = $oFO->fetchIcwpRequestParam( 'site_is_wpms', '', true );

			$bResult = $this->loadWpFunctionsPlugins()->delete( $sPluginFile, $bIsWpms );
			wp_cache_flush(); // since we've deleted a plugin, we need to ensure our collection is up-to-date rebuild.
			$aPlugins = $this->getWpCollector()->collectWordpressPlugins();

			$aData = array(
				'result'			=> $bResult,
				'wordpress-plugins'	=> $aPlugins
			);
			return $this->success( $aData );
		}

		/**
		 * @return stdClass
		 */
		protected function icwpapi_plugin_install_url() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$aPlugin = $oFO->fetchIcwpRequestParam( 'plugin', '', true );
			$bIsNetworkWide = $oFO->fetchIcwpRequestParam( 'network_wide', false );

			if ( empty( $aPlugin['url'] ) ) {
				return $this->fail(
					array(),
					'The URL was empty.'
				);
			}

			$sPluginUrl = wp_http_validate_url( $aPlugin['url'] );
			if ( !$sPluginUrl ) {
				return $this->fail(
					array(),
					'The URL did not pass the WordPress HTTP URL Validation.'
				);
			}

			$oWpPlugins = $this->loadWpFunctionsPlugins();

			$aResult = $oWpPlugins->install( $sPluginUrl, $aPlugin['overwrite'] );
			if ( isset( $aResult['successful'] ) && !$aResult['successful'] ) {
				return $this->fail( implode( ' | ', $aResult['errors'] ), $aResult );
			}

			//activate as required
			$sPluginFile = $aResult['plugin_info'];
			if ( !empty( $sPluginFile ) && isset( $aPlugin['activate'] ) && $aPlugin['activate'] == 1 ) {
				$oWpPlugins->activate( $sPluginFile, $bIsNetworkWide );
			}

			wp_cache_flush(); // since we've added a plugin

			$aData = array(
				'result'			=> $aResult,
				'wordpress-plugins'	=> $this->getWpCollector()->collectWordpressPlugins()
			);
			return $this->success( $aData );
		}

		/**
		 * @return stdClass
		 */
		protected function icwpapi_theme_activate() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$sThemeFile = $oFO->fetchIcwpRequestParam( 'theme_file', '', true );
			$bResult = $this->loadWpFunctionsThemes()->activate( $sThemeFile );

			$aData = array(
				'result'			=> $bResult,
				'wordpress-themes'	=> $this->getWpCollector()->collectWordpressThemes(), //Need to send back all themes so we can update the one that got deactivated
			);
			return $this->success( $aData );
		}

		/**
		 * @return stdClass
		 */
		protected function icwpapi_theme_delete() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oWpThemes = $this->loadWpFunctionsThemes();

			$sStylesheet = $oFO->fetchIcwpRequestParam( 'theme_file', '', true );
			if ( empty( $sStylesheet ) ) {
				return $this->fail(
					array(),
					'Stylesheet provided was empty.'
				);
			}

			if ( !$oWpThemes->getExists( $sStylesheet ) ) {
				return $this->fail(
					array( 'stylesheet' => $sStylesheet ),
					sprintf( 'Theme does not exist with Stylesheet: %s', $sStylesheet )
				);
			}

			$oThemeToDelete = $oWpThemes->getTheme( $sStylesheet );
			if ( $oThemeToDelete->get_stylesheet_directory() == get_stylesheet_directory() ) {
				return $this->fail(
					array( 'stylesheet' => $sStylesheet ),
					sprintf( 'Cannot uninstall the currently active WordPress theme: %s', $sStylesheet )
				);
			}

			$mResult = $oWpThemes->delete( $sStylesheet );

			$aData = array(
				'result'			=> $mResult,
				'wordpress-themes'	=> $this->getWpCollector()->collectWordpressThemes(), //Need to send back all themes so we can update the one that got deleted
			);
			return $this->success( $aData );
		}

		/**
		 * @return stdClass
		 */
		protected function icwpapi_theme_install_url() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();

			$aTheme = $oFO->fetchIcwpRequestParam( 'theme', '', true );

			if ( empty( $aTheme['url'] ) ) {
				return $this->fail(
					array(),
					'The URL was empty.'
				);
			}

			$sUrl = wp_http_validate_url( $aTheme['url'] );
			if ( !$sUrl ) {
				return $this->fail(
					array(),
					'The URL did not pass the WordPress HTTP URL Validation.'
				);
			}

			$oWpThemes = $this->loadWpFunctionsThemes();

			$aResult = $oWpThemes->install( $sUrl, $aTheme['overwrite'] );
			if ( isset( $aResult['successful'] ) && !$aResult['successful'] ) {
				return $this->fail( implode( ' | ', $aResult['errors'] ), $aResult );
			}

			$oInstalledTheme = $aResult[ 'theme_info' ];

			if ( is_string( $oInstalledTheme ) ) {
				$oInstalledTheme = wp_get_theme( $oInstalledTheme );
			}
			if ( !is_object( $oInstalledTheme ) || !$oInstalledTheme->exists() ) {
				return $this->fail( array(), 'After installation, cannot load the theme.' );
			}

			if ( isset( $aTheme['activate'] ) && $aTheme['activate'] == '1' ) {
				if ( $oInstalledTheme->get_stylesheet_directory() != get_stylesheet_directory() ) {
					$oWpThemes->activate( $oInstalledTheme->get_stylesheet() );
				}
			}

			$aData = array(
				'result'			=> $aResult,
				'wordpress-themes'	=> $this->getWpCollector()->collectWordpressThemes()
			);

			return $this->success( $aData );
		}

		/**
		 * @return stdClass
		 */
		protected function icwpapi_wplogin() {

			$sSource = home_url().'$'.uniqid().'$'.time();
			$sToken = md5( $sSource );
			$this->loadWpFunctionsProcessor()->setTransient( 'worpit_login_token', $sToken );

			$aData = array(
				'source'	=> $sSource,
				'token'		=> $sToken
			);
			return $this->success( $aData );
		}

		/**
		 * @param array $aExecutionData
		 * @param string $sMessage
		 * @return stdClass
		 */
		protected function fail( $aExecutionData = array(), $sMessage = '' ) {
			return $this->setErrorResponse(
				sprintf( 'Package Execution FAILED with error message: "%s"', $sMessage ),
				-1, //TODO: Set a code
				$aExecutionData
			);
		}

		/**
		 * @param array $aExecutionData
		 * @param string $sMessage
		 * @return stdClass
		 */
		protected function success( $aExecutionData = array(), $sMessage = '' ) {
			return $this->setSuccessResponse(
				sprintf( 'Package Execution SUCCEEDED with message: "%s".', $sMessage ),
				0,
				$aExecutionData
			);
		}

		/**
		 * @param string|null $sAction
		 * @return bool
		 */
		protected function isActionSupported( $sAction = null ) {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			if ( empty( $sAction ) ) {
				$sAction = $this->getCurrentApiActionName();
			}
			return in_array( $sAction, $oFO->getPermittedInternalApiAction() );
		}

		/**
		 * @param string|null $sAction
		 * @return bool
		 */
		protected function isActionDefined( $sAction = null ) {
			if ( empty( $sAction ) ) {
				$sAction = $this->getCurrentApiActionName();
			}
			return method_exists( $this, self::ApiMethodPrefix.$sAction );
		}

		/**
		 * @return string
		 */
		protected function getCurrentApiActionName() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			return $oFO->fetchIcwpRequestParam( 'action', '' );
		}

		/**
		 * @return ICWP_APP_WpCollectInfo
		 */
		protected function getWpCollector() {
			require_once( dirname( __FILE__ ) . '/../common/icwp-wpcollectinfo.php' );
			return ICWP_APP_WpCollectInfo::GetInstance();
		}
	}

endif;
