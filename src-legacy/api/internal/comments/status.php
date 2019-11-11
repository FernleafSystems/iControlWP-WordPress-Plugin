<?php

if ( class_exists( 'ICWP_APP_Api_Internal_Comments_Status', false ) ) {
	return;
}

require_once( dirname( dirname( __FILE__ ) ).'/base.php' );

class ICWP_APP_Api_Internal_Comments_Status extends ICWP_APP_Api_Internal_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		if ( !function_exists( 'wp_set_comment_status' ) ) {
			return $this->fail( 'WordPress function "wp_set_comment_status" is not available.' );
		}

		$oWpComments = $this->loadWpCommentsProcessor();

		$aResults = array();
		$aActionParams = $this->getActionParams();
		foreach ( $aActionParams[ 'comments_and_status' ] as $nCommentId => $sStatus ) {

			$aResults[ $nCommentId ] = $oWpComments->setCommentStatus( $nCommentId, $sStatus );
			/* did it work?
			$mNewStatus = $oWpComments->getCommentStatus( $nCommentId );
			if ( $mNewStatus == $sStatus ) {
				$aResults[ $nCommentId ] = true;
			}
			else if ( $mNewStatus === false && $sStatus == 'delete' ) {
				$aResults[ $nCommentId ] = true;
			}
			else {
				$aResults[ $nCommentId ] = false;
			} */
		}

		$aData = array( 'results' => $aResults );
		return $this->success( $aData );
	}
}