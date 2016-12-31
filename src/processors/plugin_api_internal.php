<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api_Internal', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'plugin_api.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api_Internal
	 */
	class ICWP_APP_Processor_Plugin_Api_Internal extends ICWP_APP_Processor_Plugin_Api {

		/**
		 * @return ApiResponse|mixed
		 */
		protected function processAction() {
			$sActionName = $this->getCurrentApiActionName();
			if ( !$this->isActionSupported( $sActionName ) ) {
				return $this->setErrorResponse(
					sprintf( 'Action "%s" is not currently supported.', $sActionName )
					-1 //TODO: Set a code
				);
			}
			return $this->process();
		}

		/**
		 * @return ApiResponse
		 */
		protected function process() {
			$sActionName = $this->getCurrentApiActionName();
			if ( $sActionName == 'wplogin' ) {
				$sActionName = 'wordpress_login';
			}
			$aParts = explode( '_', $sActionName );

			$sBase = dirname( dirname( __FILE__ ) ).DIRECTORY_SEPARATOR.'api'.DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR;
			$sFullPath = $sBase.$aParts[0].DIRECTORY_SEPARATOR.$aParts[1].'.php';
			require_once( $sFullPath );

			/** @var ICWP_APP_Api_Internal_Base $oApi */
			$sClassName = 'ICWP_APP_Api_Internal_'.ucfirst( $aParts[ 0 ] ).'_'.ucfirst( $aParts[ 1 ] );
			$oApi = new $sClassName();
			$oApi->setRequestParams( $this->getRequestParams() )
				 ->setStandardResponse( $this->getStandardResponse() );
			return call_user_func( array( $oApi, 'process' ) );
		}

		/**
		 * @param string $sAction
		 * @return bool
		 */
		protected function isActionSupported( $sAction ) {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			return in_array( $sAction, $oFO->getSupportedInternalApiAction() );
		}

		/**
		 * @return string
		 */
		protected function getCurrentApiActionName() {
			return $this->getRequestParams()->getApiAction();
		}
	}

endif;
