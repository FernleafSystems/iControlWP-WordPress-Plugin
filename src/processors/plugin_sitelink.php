<?php

if ( !class_exists( 'ICWP_APP_Processor_Plugin_SiteLink', false ) ):

	require_once( dirname(__FILE__).ICWP_DS.'base_app.php' );

	class ICWP_APP_Processor_Plugin_SiteLink extends ICWP_APP_Processor_BaseApp {

		/**
		 * @return ApiResponse
		 */
		public function run() {
			require_once( dirname( dirname( __FILE__ ) ) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'ApiResponse.php' );
			$oResponse = ( new ApiResponse() )
				->setStatus( '' )
				->setCode( 0 )
				->setSuccess( false );

			/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
			$oFO = $this->getFeatureOptions();
			$oReqParams = $this->getRequestParams();

			if ( $oFO->getIsSiteLinked() ) {
				$oResponse->setMessage( 'Assigned To:' . $this->getOption( 'assigned_to' ) )
						  ->setStatus( 'AlreadyAssigned' )
						  ->setCode( 1 );
			}

			// First is the check to see that we can simply call the site and communicate with the plugin
			if ( $oReqParams->getStringParam( 'a' ) == 'check' ) {
				return $oResponse->setSuccess( true );
			}

			// At this point we're in the 2nd stage of the link...

			// bail immediately if we're already assigned
			if ( $oResponse->getStatus() == 'AlreadyAssigned' ) {
				return $oResponse;
			}

			$sRequestedKey = $oReqParams->getAuthKey();
			if ( empty( $sRequestedKey ) ) {
				return $oResponse->setMessage( 'KeyEmpty:'.'.' )
								 ->setCode( 2 );
			}
			if ( $sRequestedKey != $oFO->getPluginAuthKey() ) {
				return $oResponse->setMessage( 'KeyMismatch:'.$sRequestedKey.'.' )
								 ->setCode( 3 );
			}

			$sRequestPin = $oReqParams->getPin();
			if ( empty( $sRequestPin ) ) {
				return $oResponse->setMessage( 'PinEmpty:.' )
								 ->setCode( 4 );
			}

			$sRequestedAcc = $oReqParams->getAccountId();
			if ( empty( $sRequestedAcc ) ) {
				return $oResponse->setMessage( 'AccountEmpty:.' )
								 ->setCode( 5 );
			}
			if ( !is_email( $sRequestedAcc ) ) {
				return $oResponse->setMessage( 'AccountNotValid:'.$sRequestedAcc )
								 ->setCode( 6 );
			}

			$oFO->setPluginPin( $sRequestPin );
			$oFO->setAssignedAccount( $sRequestedAcc );

			return $oResponse->setSuccess( true );
		}
	}

endif;
