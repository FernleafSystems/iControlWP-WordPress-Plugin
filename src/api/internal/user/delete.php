<?php

class ICWP_APP_Api_Internal_User_Delete extends ICWP_APP_Api_Internal_Base {

	/**
	 * @inheritDoc
	 */
	public function process() {

		//Ensure we have the delete function available

		$aActionParams = $this->getActionParams();
		$nUserId = (int)$aActionParams[ 'user_id' ];
		$nReassignUserId = isset( $aActionParams[ 'reassign_id' ] ) ? $aActionParams[ 'reassign_id' ] : null;

		// Validate User ID

		try {
			$bResult = $this->loadWpUsers()->deleteUser(
				$nUserId,
				false,
				$nReassignUserId
			);
		}
		catch ( Exception $oE ) {
			return $this->fail( $oE->getMessage() );
		}

		$aData = [ 'result' => $bResult ];
		return $this->success( $aData );
	}
}