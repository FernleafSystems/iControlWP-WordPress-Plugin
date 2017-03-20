<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Base', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Base extends ICWP_APP_Api_Internal_Base {

		/**
		 * @param string $sContext
		 * @return mixed
		 */
		protected function getAutoUpdates( $sContext = 'plugins' ) {
			return ICWP_Plugin::GetAutoUpdatesSystem()->getAutoUpdates( $sContext );
		}

		/**
		 * @return bool
		 */
		protected function isForceUpdateCheck() {
			$aActionParams = $this->getActionParams();
			return isset( $aActionParams[ 'force_update_check' ] ) ? (bool)$aActionParams[ 'force_update_check' ] : true;
		}
	}

endif;