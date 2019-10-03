<?php

class ICWP_APP_Api_Internal_Theme_Activate extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$aActionParams = $this->getActionParams();
		$bResult = $this->loadWpFunctionsThemes()->activate( $aActionParams[ 'theme_file' ] );

		$aData = array(
			'result'           => $bResult,
			'wordpress-themes' => $this->getWpCollector()->collectWordpressThemes(),
			//Need to send back all themes so we can update the one that got deactivated
		);
		return $this->success( $aData );
	}
}