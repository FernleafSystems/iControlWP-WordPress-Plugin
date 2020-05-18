<?php

abstract class ICWP_APP_Processor_BaseApp extends ICWP_APP_Processor_Base {

	/**
	 * @return \FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi\RequestParameters
	 */
	protected function getRequestParams() {
		return $this->getFeatureOptions()->getRequestParams();
	}

	/**
	 * @return array
	 */
	protected function getActionParams() {
		return $this->getRequestParams()->getActionParams();
	}
}