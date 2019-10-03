<?php

class ICWP_APP_Api_Internal_Collect_Info extends ICWP_APP_Api_Internal_Collect_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$aData = array(
			'capabilities'       => $this->getCollector_Capabilities()->collect(),
			'wordpress-info'     => $this->getCollector_WordPressInfo()->collect(),
			'wordpress-paths'    => $this->getCollector_Paths()->collect(),
			'wordpress-plugins'  => $this->collectPlugins(),
			'wordpress-themes'   => $this->collectThemes(),
			'force_update_check' => $this->isForceUpdateCheck() ? 1 : 0,
		);

		return $this->success( $aData );
	}
}