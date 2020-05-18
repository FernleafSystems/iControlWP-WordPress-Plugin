<?php

class ICWP_APP_Api_Internal_Plugin_Deactivate extends ICWP_APP_Api_Internal_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {
		$aActionParams = $this->getActionParams();
		$sPluginFile = $aActionParams[ 'plugin_file' ];
		$bIsWpms = $aActionParams[ 'site_is_wpms' ];

		$this->loadWpFunctionsPlugins()->deactivate( $sPluginFile, $bIsWpms );
		$aPlugin = $this->getWpCollector()->collectWordpressPlugins( $sPluginFile );
		$aData = [
			'result'        => true,
			'single-plugin' => $aPlugin[ $sPluginFile ]
		];
		return $this->success( $aData );
	}
}