<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Info', false ) ):

	require_once( dirname( __FILE__ ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Info extends ICWP_APP_Api_Internal_Collect_Base {
		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aData = array(
				'wordpress-plugins' => $this->collectPlugins(),
				'wordpress-themes' => $this->collectThemes(),
			);
			return $this->success( $aData );
		}

		/**
		 * @return array
		 */
		private function collectPlugins() {
			require_once( dirname( __FILE__ ).ICWP_DS.'plugins.php' );
			$oCollector = new ICWP_APP_Api_Internal_Collect_Plugins();
			$oCollector->setRequestParams( $this->getRequestParams() );
			return $oCollector->collect( true );
		}

		/**
		 * @return array
		 */
		private function collectThemes() {
			require_once( dirname( __FILE__ ).ICWP_DS.'themes.php' );
			$oCollector = new ICWP_APP_Api_Internal_Collect_Themes();
			$oCollector->setRequestParams( $this->getRequestParams() );
			return $oCollector->collect( true );
		}
	}

endif;