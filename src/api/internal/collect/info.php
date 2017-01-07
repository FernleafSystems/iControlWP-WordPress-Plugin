<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Info', false ) ):

	require_once( dirname( __FILE__ ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Info extends ICWP_APP_Api_Internal_Collect_Base {
		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aData['wordpress-plugins'] = $this->collectWordpressPlugins( true );
			$aData['wordpress-themes'] = $this->collectWordpressThemes( true );
			return $this->success( $aData );
		}
	}

endif;