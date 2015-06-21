<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api_Internal', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api_Internal
	 */
	class ICWP_APP_Processor_Plugin_Api_Internal extends ICWP_APP_Processor_Plugin_Api {

		const ApiMethodPrefix = 'icwpapi_';

		/**
		 * @var stdClass
		 */
		protected $oActionDefinition;

		/**
		 * @return stdClass
		 */
		public function run() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oResponse = $this->getStandardResponse();

			// Always verify
			$this->doHandshakeVerify();
			if ( !$oResponse->success ) {
				if ( $oResponse->code == 9991 ) {
					$oFO->setCanHandshake(); //recheck ability to handshake
				}
				return $oResponse;
			}

			$this->preApiCheck();
			if ( !$oResponse->success ) {
				if ( !$this->doAttemptSiteReassign()->success ) {
					return $oResponse;
				}
			}

			$this->doWpEngine();
			@set_time_limit( $oFO->fetchIcwpRequestParam( 'timeout', 60 ) );

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
		 * @param $aData
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
