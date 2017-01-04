<?php

/**
 * Class ApiResponse
 */
class ApiResponse {

	/**
	 * @var stdClass
	 */
	protected $oResponsePackageData;

	/**
	 * @return int
	 */
	public function getCode() {
		return $this->getResponseItem( 'code' );
	}

	/**
	 * @return array
	 */
	public function getData() {
		$aData = $this->getResponseItem( 'data' );
		if ( is_null( $aData ) || !is_array( $aData ) ) {
			$aData = array();
			$this->setResponseItem( 'data', $aData );
		}
		return $aData;
	}

	/**
	 * @param string $sItem
	 * @param null $mDefault
	 * @return mixed|null
	 */
	public function getDataItem( $sItem, $mDefault = null ) {
		$aData = $this->getData();
		return isset( $aData[ $sItem ] ) ? $aData[ $sItem ] : $mDefault;
	}

	/**
	 * @return string
	 */
	public function getErrorMessage() {
		return $this->getResponseItem( 'error_message' );
	}

	/**
	 * @param string $sItem
	 * @param mixed $mDefault
	 * @return mixed
	 */
	protected function getResponseItem( $sItem, $mDefault = null ) {
		$oPackage = $this->getResponsePackage();
		return isset( $oPackage->{$sItem} ) ? $oPackage->{$sItem} : $mDefault;
	}

	/**
	 * @return string
	 */
	public function getStatus() {
		return $this->getResponseItem( 'status' );
	}

	/**
	 * @return bool
	 */
	public function isDie() {
		return (bool)$this->getResponseItem( 'die', false );
	}

	/**
	 * @return bool
	 */
	public function isSuccessful() {
		return (bool)$this->getResponseItem( 'success', false );
	}

	/**
	 * @param string $sChannel
	 * @return $this
	 */
	public function setChannel( $sChannel ) {
		return $this->setResponseItem( 'channel', $sChannel );
	}

	/**
	 * @param array $aData
	 * @return $this
	 */
	public function setData( $aData ) {
		return $this->setResponseItem( 'data', $aData );
	}

	/**
	 * @param string $sItem
	 * @param mixed $mValue
	 * @return ApiResponse
	 */
	public function setDataItem( $sItem, $mValue ) {
		$aData = $this->getData();
		$aData[ $sItem ] = $mValue;
		return $this->setData( $aData );
	}

	/**
	 * @param int $nCode
	 * @return $this
	 */
	public function setCode( $nCode ) {
		return $this->setResponseItem( 'code', (int)$nCode );
	}

	/**
	 * @param string $sMsg
	 * @return $this
	 */
	public function setErrorMessage( $sMsg ) {
		return $this->setResponseItem( 'error_message', $sMsg );
	}

	/**
	 * @param bool $bDie
	 * @return $this
	 */
	public function setDie( $bDie = false ) {
		return $this->setResponseItem( 'die', $bDie ? 1 : 0 );
	}

	/**
	 * @return $this
	 */
	public function setFailed() {
		return $this->setSuccess( false );
	}

	/**
	 * @param string $sMethod
	 * @return $this
	 */
	public function setHandshakeMethod( $sMethod ) {
		return $this->setResponseItem( 'handshake', $sMethod );
	}

	/**
	 * @param string $sMsg
	 * @return $this
	 */
	public function setMessage( $sMsg ) {
		return $this->setResponseItem( 'message', $sMsg );
	}

	/**
	 * @param int $nResult
	 * @return $this
	 */
	public function setOpensslVerify( $nResult ) {
		return $this->setResponseItem( 'openssl_verify', $nResult );
	}

	/**
	 * @param bool $bSuccess
	 * @return $this
	 */
	public function setSuccess( $bSuccess = true ) {
		return $this->setResponseItem( 'success', $bSuccess );
	}

	/**
	 * @param string $sStatus
	 * @return $this
	 */
	public function setStatus( $sStatus ) {
		return $this->setResponseItem( 'status', $sStatus );
	}

	/**
	 * @param string $sItem
	 * @param mixed $mValue
	 * @return $this
	 */
	protected function setResponseItem( $sItem, $mValue ) {
		$this->getResponsePackage()->{$sItem} = $mValue;
		return $this;
	}

	/**
	 * @return stdClass
	 */
	public function getResponsePackage() {
		if ( is_null( $this->oResponsePackageData ) ) {
			$oResponse = new stdClass();
			$oResponse->error_message = '';
			$oResponse->message = '';
			$oResponse->success = true;
			$oResponse->code = 0;
			$oResponse->channel = '';
			$oResponse->die = false;
			$oResponse->handshake = 'none';
			$oResponse->openssl_verify = -999;
			$oResponse->data = array();

			$this->oResponsePackageData = $oResponse;
		}
		return $this->oResponsePackageData;
	}
}