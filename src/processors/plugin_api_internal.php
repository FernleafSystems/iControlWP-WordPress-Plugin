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
			$sPluginFile = unserialize( base64_decode( $oFO->fetchIcwpRequestParam( 'plugin_file', '' ) ) );
			$bIsWpms = unserialize( base64_decode( $oFO->fetchIcwpRequestParam( 'site_is_wpms', '' ) ) );

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
			$sPluginFile = unserialize( base64_decode( $oFO->fetchIcwpRequestParam( 'plugin_file', '' ) ) );
			$bIsWpms = unserialize( base64_decode( $oFO->fetchIcwpRequestParam( 'site_is_wpms', '' ) ) );

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
			$sPluginFile = unserialize( base64_decode( $oFO->fetchIcwpRequestParam( 'plugin_file', '' ) ) );
			$bIsWpms = unserialize( base64_decode( $oFO->fetchIcwpRequestParam( 'site_is_wpms', '' ) ) );

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
		 * @param array $aData
		 * @param string $sMessage
		 * @return stdClass
		 */
		protected function fail( $aData, $sMessage = '' ) {
			$aResponse = array(
				'success'			=> false,
				'message'			=> $sMessage,
				'error'				=> $sMessage,
				'data'				=> $this->encodeDataForResponse( $aData ),
				'base64response'	=> true
			);
			return $this->processExecutionFinalResponse( $aResponse );
		}

		/**
		 * @param $aData
		 * @param string $sMessage
		 * @return stdClass
		 */
		protected function success( $aData, $sMessage = '' ) {
			$aResponse = array(
				'success'			=> true,
				'message'			=> $sMessage,
				'data'				=> $this->encodeDataForResponse( $aData ),
				'base64response'	=> true
			);
			return $this->processExecutionFinalResponse( $aResponse );
		}

		/**
		 * @param array $aData
		 * @return string
		 */
		protected function encodeDataForResponse( $aData ) {
//			$this->encryptResponseData( $aResponse ); //TODO
			return $this->pad( base64_encode( serialize( $aData ) ) );
		}

		/**
		 * @param string $sString
		 * @return string
		 */
		protected function pad( $sString ) {
			return '==PAD=='.$sString.'==PAD==';
		}

		/**
		 * @param string|null $sAction
		 * @return bool
		 */
		protected function isActionSupported( $sAction = null ) {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			return in_array( empty( $sAction ) ? $this->getCurrentApiActionName() : $sAction, $oFO->getOpt( 'internal_api_supported_actions' ) );
		}

		/**
		 * @param string|null $sAction
		 * @return bool
		 */
		protected function isActionDefined( $sAction = null ) {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			if ( is_null( $sAction ) ) {
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
