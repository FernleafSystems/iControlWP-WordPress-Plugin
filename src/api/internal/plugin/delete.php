<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Plugin_Delete', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

	class ICWP_APP_Api_Internal_Plugin_Delete extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aActionParams = $this->getActionParams();
			$sPluginFile = $aActionParams[ 'plugin_file' ];
			$bIsWpms = $aActionParams[ 'site_is_wpms' ];

			$bResult = $this->loadWpFunctionsPlugins()->delete( $sPluginFile, $bIsWpms );
			wp_cache_flush(); // since we've deleted a plugin, we need to ensure our collection is up-to-date rebuild.

			$aData = array(
				'result'			=> $bResult,
				'wordpress-plugins'	=> $this->getWpCollector()->collectWordpressPlugins()
			);
			return $bResult ? $this->success( $aData ) : $this->fail( $aData );
		}
	}

endif;