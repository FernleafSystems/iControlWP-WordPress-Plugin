<?php

class ICWP_APP_Api_Internal_Plugin_Install extends ICWP_APP_Api_Internal_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {
		$aPlugin = $this->getActionParams();

		if ( empty( $aPlugin[ 'url' ] ) ) {
			return $this->fail(
				[],
				'The URL was empty.'
			);
		}

		$sPluginUrl = wp_http_validate_url( $aPlugin[ 'url' ] );
		if ( !$sPluginUrl ) {
			return $this->fail(
				'The URL did not pass the WordPress HTTP URL Validation.'
			);
		}

		$oWpPlugins = $this->loadWpFunctionsPlugins();

		$aResult = $oWpPlugins->install( $sPluginUrl, $aPlugin[ 'overwrite' ] );
		if ( empty( $aResult[ 'successful' ] ) ) {
			return $this->fail( implode( ' | ', $aResult[ 'errors' ] ), -1, $aResult );
		}

		//activate as required
		$sPluginFile = $aResult[ 'plugin_info' ];
		if ( !empty( $sPluginFile ) && isset( $aPlugin[ 'activate' ] ) && $aPlugin[ 'activate' ] == 1 ) {
			$oWpPlugins->activate( $sPluginFile, $aPlugin[ 'network_wide' ] );
		}

		wp_cache_flush(); // since we've added a plugin

		$aData = [
			'result'            => $aResult,
			'wordpress-plugins' => $this->getWpCollector()->collectWordpressPlugins()
		];
		return $this->success( $aData );
	}
}