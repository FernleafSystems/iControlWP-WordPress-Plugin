<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

/**
 * Class ICWP_APP_Processor_Plugin_Api_Index
 */
class ICWP_APP_Processor_Plugin_Api_Status extends ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return LegacyApi\ApiResponse
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
		$oCon = $this->getController();
		return [
			'plugin_status'      => 1,
			'plugin_version'     => $oCon->getVersion(),
			'plugin_url'         => $oCon->getPluginUrl(),
			'supported_internal' => $oFO->getSupportedInternalApiAction(),
			'supported_modules'  => $oFO->getSupportedModules(),
			'supported_channels' => $oFO->getPermittedApiChannels(),
			'supported_openssl'  => $this->loadEncryptProcessor()->getSupportsOpenSslSign() ? 1 : 0,
			'wpe_api'            => defined( 'WPE_API' ),
		];
	}
}
