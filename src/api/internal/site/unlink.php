<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Site_Unlink', false ) ):

	require_once( dirname( dirname( __FILE__ ) ) . ICWP_DS . 'base.php' );

	class ICWP_APP_Api_Internal_Site_Unlink extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aActionParams = $this->getActionParams();
			if ( class_exists( 'ICWP_Plugin', false ) && method_exists( 'ICWP_Plugin', 'updateOption' ) ) {
				ICWP_Plugin::updateOption( 'key',			$aActionParams[ 'auth_key' ] );
				ICWP_Plugin::updateOption( 'pin',			'' );
				ICWP_Plugin::updateOption( 'assigned',		'N' );
				ICWP_Plugin::updateOption( 'assigned_to',	'' );
				$sServicePluginBaseFile = ICWP_Plugin::getController()->getPluginBaseFile();
				deactivate_plugins( $sServicePluginBaseFile, '', is_multisite() );
			}
			else {
				do_action( 'icwp-app-SiteUnlink' );
			}
		}
	}

endif;