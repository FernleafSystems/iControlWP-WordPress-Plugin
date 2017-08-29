<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_Api_Retrieve', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'plugin_api.php' );

	/**
	 * Class ICWP_APP_Processor_Plugin_Api_Retrieve
	 */
	class ICWP_APP_Processor_Plugin_Api_Retrieve extends ICWP_APP_Processor_Plugin_Api {

		/**
		 * @return ApiResponse
		 */
		protected function processAction() {
			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oReqParams = $this->getRequestParams();
			$oFs = $this->loadFS();

			if ( !function_exists( 'download_url' ) ) {
				return $this->setErrorResponse(
					sprintf( 'Function "%s" does not exit.', 'download_url' )
					-1 //TODO: Set a code
				);
			}

			if ( !function_exists( 'is_wp_error' ) ) {
				return $this->setErrorResponse(
					sprintf( 'Function "%s" does not exit.', 'is_wp_error' ),
					-1 //TODO: Set a code
				);
			}

			$sPackageId = $oReqParams->getStringParam( 'package_id' );
			if ( empty( $sPackageId ) ) {
				return $this->setErrorResponse(
					'Package ID to retrieve is empty.',
					-1 //TODO: Set a code
				);
			}

			// We can do this because we've assumed at this point we've validated the communication with iControlWP
			$sRetrieveBaseUrl = $oReqParams->getStringParam( 'package_retrieve_url', $this->getOption( 'package_retrieve_url' ) );
			$sPackageRetrieveUrl = sprintf(
				'%s/%s/%s/%s',
				rtrim( $sRetrieveBaseUrl, '/' ),
				$sPackageId,
				$oFO->getPluginAuthKey(),
				$oFO->getPluginPin()
			);
			$sRetrievedTmpFile = download_url( $sPackageRetrieveUrl );

			if ( is_wp_error( $sRetrievedTmpFile ) ) {
				$sMessage = sprintf(
					'The package could not be downloaded from "%s" with error: %s',
					$sPackageRetrieveUrl,
					$sRetrievedTmpFile->get_error_message()
				);
				return $this->setErrorResponse(
					$sMessage,
					-1 //TODO: Set a code
				);
			}

			$sNewFile = $this->getController()->getPath_Temp( basename( $sRetrievedTmpFile ) );
//			if ( is_null( $sNewFile ) ) {
//				return $this->setErrorResponse(
//					'Could not create temporary folder to store package',
//					-1 //TODO: Set a code
//				);
//			}
			$sFileToInclude = $sRetrievedTmpFile;
			if ( !is_null( $sNewFile ) && $oFs->move( $sRetrievedTmpFile, $sNewFile ) ) { //we try to move it to our plugin tmp folder.
				$sFileToInclude = $sNewFile;
			}

			$oExecutionResponse = $this->runInstaller( $sFileToInclude );
			$oFs->deleteFile( $sFileToInclude );
			return $oExecutionResponse;
		}
	}

endif;
