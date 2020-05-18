<?php

class ICWP_APP_Api_Internal_Core_Dbupgrade extends ICWP_APP_Api_Internal_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {
		$this->loadWP()->doWpUpgrade();
		return $this->success();
	}
}