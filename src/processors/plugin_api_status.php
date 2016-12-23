<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api_Status', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'plugin_api.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api_Index
	 */
	class ICWP_APP_Processor_Plugin_Api_Status extends ICWP_APP_Processor_Plugin_Api {

		/**
		 * @return stdClass
		 */
		protected function processAction() {
			return $this->setSuccessResponse( 'Status', 0, $this->getStatusData() );
		}

		/**
		 * @return array
		 */
		protected function getStatusData() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			return array(
				'plugin_version' => $this->getController()->getVersion(),
				'support_internal' => $oFO->getSupportedInternalApiAction(),
				'support_modules' => $oFO->getSupportedModules(),
				'support_channels' => $oFO->getPermittedApiChannels(),
				'support_openssl' => $this->loadEncryptProcessor()->getSupportsOpenSslSign() ? 1 : 0,
			);
		}
	}

endif;
