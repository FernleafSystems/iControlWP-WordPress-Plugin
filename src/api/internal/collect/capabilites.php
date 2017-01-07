<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Capabilities', false ) ):

	require_once( dirname( __FILE__ ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Capabilities extends ICWP_APP_Api_Internal_Collect_Base {
		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aData = array(
				'capabilities' => $this->collect()
			);
			return $this->success( $aData );
		}

		/**
		 * @return array
		 */
		public function collect() {
			return array(
				'is_force_ssl_admin' => function_exists( 'force_ssl_admin' ) && force_ssl_admin(),
				'handshake_enabled' => ICWP_Plugin::GetHandshakingEnabled() ? 1 : 0,
			);
		}
	}

endif;