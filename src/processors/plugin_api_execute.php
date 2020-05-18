<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

/**
 * Class ICWP_APP_Processor_Plugin_Api_Execute
 */
class ICWP_APP_Processor_Plugin_Api_Execute extends ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return LegacyApi\ApiResponse
	 */
	protected function processAction() {
		$oFs = $this->loadFS();

		/**
		 * @since 1.0.14
		 */
		$_POST[ 'rel_package_dir' ] = '';
		$_POST[ 'abs_package_dir' ] = '';

		$sTempDir = $oFs->getTempDir( $this->getController()->getPath_Temp(), 'pkg_' );
		if ( !isset( $_POST[ 'force_use_eval' ] ) ) {
			$_POST[ 'rel_package_dir' ] = str_replace( dirname( __FILE__ ), '', $sTempDir );
			$_POST[ 'abs_package_dir' ] = $sTempDir;
		}
		else {
			return $this->setErrorResponse(
				'No longer support EVAL() methods.',
				9800
			);
		}

		// TODO:
		//https://yoast.com/smarter-upload-handling-wp-plugins/
		//wp_handle_upload()
		foreach ( $_FILES as $sKey => $aUpload ) {
			if ( $aUpload[ 'error' ] == UPLOAD_ERR_OK ) {
				$sMoveTarget = $sTempDir.DIRECTORY_SEPARATOR.$aUpload[ 'name' ];
				if ( !move_uploaded_file( $aUpload[ 'tmp_name' ], $sMoveTarget ) ) {
					return $this->setErrorResponse(
						sprintf( 'Failed to move uploaded file from %s to %s', $aUpload[ 'tmp_name' ], $sMoveTarget ),
						9801
					);
				}
				chmod( $sMoveTarget, 0644 );
			}
			else {
				return $this->setErrorResponse(
					'One of the uploaded files could not be copied to the temp dir.',
					9802
				);
			}
		}

		$sFileToInclude = $sTempDir.'/installer.php';
		$oExecutionResponse = $this->runInstaller( $sFileToInclude );
		$oFs->deleteDir( $sTempDir );
		return $oExecutionResponse;
	}
}