<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Plugins', false ) ):

	require_once( dirname( __FILE__ ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Plugins extends ICWP_APP_Api_Internal_Collect_Base {
		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aData['wordpress-plugins'] = $this->collectWordpressPlugins( true );
			return $this->success( $aData );
		}
	}

endif;