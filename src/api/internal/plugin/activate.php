<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Plugin_Activate', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Plugin_Activate extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aActionParams = $this->getActionParams();
			$sPluginFile = $aActionParams[ 'plugin_file' ];
			$bIsWpms = $aActionParams[ 'site_is_wpms' ];

			$bResult = $this->loadWpFunctionsPlugins()->activate( $sPluginFile, $bIsWpms );
			$aPlugin = $this->getWpCollector()->collectWordpressPlugins( $sPluginFile );
			$aData = array(
				'result'			=> $bResult,
				'single-plugin'		=> $aPlugin[ $sPluginFile ]
			);
			return $this->success( $aData );
		}
	}

endif;