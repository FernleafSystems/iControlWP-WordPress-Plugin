<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Theme_Activate', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Theme_Activate extends ICWP_APP_Api_Internal_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aActionParams = $this->getActionParams();
			$sFile = $aActionParams[ 'theme_file' ];
			$bResult = $this->loadWpFunctionsThemes()->activate( $sFile );

			$aData = array(
				'result'			=> $bResult,
				'wordpress-themes'	=> $this->getWpCollector()->collectWordpressThemes(), //Need to send back all themes so we can update the one that got deactivated
			);
			return $this->success( $aData );
		}
	}

endif;