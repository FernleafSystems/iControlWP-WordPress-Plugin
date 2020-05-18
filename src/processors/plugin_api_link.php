<?php

use FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

class ICWP_APP_Processor_Plugin_SiteLink extends ICWP_APP_Processor_Plugin_Api {

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function run() {
		$this->preActionEnvironmentSetup();
		if ( $this->getRequestParams()->getStringParam( 'a' ) == 'check' ) {
			return $this->getStandardResponse()->setSuccess( true );
		}
		return $this->processAction();
	}

	/**
	 * @return LegacyApi\ApiResponse
	 */
	public function processAction() {
		/** @var ICWP_APP_FeatureHandler_Plugin $oMod */
		$oMod = $this->getFeatureOptions();
		$oReqParams = $this->getRequestParams();
		$oResponse = $this->getStandardResponse();

		if ( $oMod->getIsSiteLinked() ) {
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
		if ( $sRequestedKey != $oMod->getPluginAuthKey() ) {
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
			$sPublicKey = $oMod->getIcwpPublicKey();
			if ( !empty( $sSignature ) && !empty( $sPublicKey ) ) {
				$nSslSuccess = $oEncryptProcessor->verifySslSignature( $sVerificationCode, $sSignature, $sPublicKey );
				$oResponse->openssl_verify = $nSslSuccess;
				if ( $nSslSuccess !== 1 ) {
					$oResponse->message = 'Failed to Verify SSL Signature.';
					$oResponse->code = 7;
					return $oResponse;
				}
			}
		}

		$oMod->setPluginPin( $sRequestPin );
		$oMod->setAssignedAccount( $sRequestedAcc );
		return $oResponse->setSuccess( true );
	}
}