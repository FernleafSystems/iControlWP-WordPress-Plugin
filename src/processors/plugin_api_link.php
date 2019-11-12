<?php

class ICWP_APP_Processor_Plugin_SiteLink extends ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return ApiResponse
	 */
	public function run() {
		$this->preActionEnvironmentSetup();
		if ( $this->getRequestParams()->getStringParam( 'a' ) == 'check' ) {
			return $this->getStandardResponse()->setSuccess( true );
		}
		return $this->processAction();
	}

	/**
	 * @return ApiResponse
	 */
	public function processAction() {
		/** @var ICWP_APP_FeatureHandler_Plugin $oFO */
		$oFO = $this->getFeatureOptions();
		$oReqParams = $this->getRequestParams();
		$oResponse = $this->getStandardResponse();

		if ( $oFO->getIsSiteLinked() ) {
			return $oResponse->setMessage( 'Assigned To:'.$this->getOption( 'assigned_to' ) )
							 ->setStatus( 'AlreadyAssigned' )
							 ->setCode( 1 )
							 ->setSuccess( false );
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

		$sVerificationCode = $oReqParams->getVerificationCode(); //the same as the authkey
		$oEncryptProcessor = $this->loadEncryptProcessor();
		if ( $oEncryptProcessor->getSupportsOpenSslSign() ) {

			$sSignature = $oReqParams->getOpenSslSignature();
			$sPublicKey = $oFO->getIcwpPublicKey();
			if ( !empty( $sSignature ) && !empty( $sPublicKey ) ) {
				$nSslSuccess = $oEncryptProcessor->verifySslSignature( $sVerificationCode, $sSignature, $sPublicKey );
				$oResponse->setOpensslVerify( $nSslSuccess );
				if ( $nSslSuccess !== 1 ) {
					return $oResponse->setMessage( 'Failed to Verify SSL Signature.' )
									 ->setCode( 7 );
				}
			}
		}

		$oFO->setPluginPin( $sRequestPin );
		$oFO->setAssignedAccount( $sRequestedAcc );
		return $oResponse->setSuccess( true );
	}
}