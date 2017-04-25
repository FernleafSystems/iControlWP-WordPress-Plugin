<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Info', false ) ):

	require_once( dirname( __FILE__ ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Info extends ICWP_APP_Api_Internal_Collect_Base {
		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aData = array(
				'capabilities' => $this->collectCapabilities(),
				'wordpress-plugins' => $this->collectPlugins(),
				'wordpress-themes' => $this->collectThemes(),
				'wordpress-info' => $this->collectWordpress(),
				'wordpress-extras' => array(
					'preferred-core-update'	=> get_preferred_from_update_core()
				),
				'wordpress-paths' => array(
					'wordpress_admin_url'	=> network_admin_url()
				),
				'force_update_check' => $this->isForceUpdateCheck() ? 1 : 0
			);
			return $this->success( $aData );
		}

		/**
		 * @return array
		 */
		private function collectCapabilities() {
			require_once( dirname( __FILE__ ).ICWP_DS.'capabilites.php' );
			$oCollector = new ICWP_APP_Api_Internal_Collect_Capabilities();
			$oCollector->setRequestParams( $this->getRequestParams() );
			return $oCollector->collect();
		}

		/**
		 * @return array
		 */
		private function collectWordpress() {
			require_once( dirname( __FILE__ ).ICWP_DS.'wordpress.php' );
			$oCollector = new ICWP_APP_Api_Internal_Collect_Wordpress();
			$oCollector->setRequestParams( $this->getRequestParams() );
			return $oCollector->collect();
		}
	}

endif;