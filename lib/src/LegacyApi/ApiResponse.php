<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class ApiResponse
 * @package FernleafSystems\Wordpress\Plugin\iControlWP\LegacyApi
 * @property int    authenticated
 * @property string channel
 * @property int    code
 * @property array  data
 * @property bool   die
 * @property string handshake
 * @property string error_message
 * @property string message
 * @property int    openssl_verify
 * @property bool   success
 * @property string status
 */
class ApiResponse {

	use StdClassAdapter;

	public function __construct() {
		$this->applyFromArray( [
			'error_message'  => '',
			'message'        => '',
			'success'        => true,
			'authenticated'  => 0,
			'channel'        => '',
			'die'            => false,
			'handshake'      => 'none',
			'openssl_verify' => -999,
			'data'           => [],
		] );
	}

	/**
	 * @param array $aData
	 * @return $this
	 */
	public function setData( $aData ) {
		$this->data = $aData;
		return $this;
	}

	/**
	 * @param int $nCode
	 * @return $this
	 */
	public function setCode( $nCode ) {
		$this->code = (int)$nCode;
		return $this;
	}

	/**
	 * @param string $sMsg
	 * @return $this
	 */
	public function setErrorMessage( $sMsg ) {
		$this->error_message = (int)$sMsg;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function setFailed() {
		return $this->setSuccess( false );
	}

	/**
	 * @param string $sMsg
	 * @return $this
	 * @deprecated
	 */
	public function setMessage( $sMsg ) {
		$this->message = $sMsg;
		return $this;
	}

	/**
	 * @param bool $bSuccess
	 * @return $this
	 * @deprecated
	 */
	public function setSuccess( $bSuccess = true ) {
		$this->success = (bool)$bSuccess;
		return $this;
	}

	/**
	 * @param string $sStatus
	 * @return $this
	 */
	public function setStatus( $sStatus ) {
		$this->status = $sStatus;
		return $this;
	}

	/**
	 * @param string $sItem
	 * @param mixed  $mValue
	 * @return $this
	 * @deprecated
	 */
	protected function setResponseItem( $sItem, $mValue ) {
		$this->{$sItem} = $mValue;
		return $this;
	}

	/**
	 * @return \stdClass
	 */
	public function getResponsePackage() {
		return (object)$this->getRawDataAsArray();
	}

	/**
	 * @param string $sItem
	 * @param mixed  $mValue
	 * @return $this
	 * @deprecated
	 */
	public function setDataItem( $sItem, $mValue ) {
		$aData = $this->getData();
		$aData[ $sItem ] = $mValue;
		return $this->setData( $aData );
	}

	/**
	 * @param int $nResult
	 * @return $this
	 * @deprecated
	 */
	public function setOpensslVerify( $nResult ) {
		$this->openssl_verify = $nResult;
		return $this;
	}

	/**
	 * @param string $sMethod
	 * @return $this
	 * @deprecated
	 */
	public function setHandshakeMethod( $sMethod ) {
		$this->handshake = $sMethod;
		return $this;
	}

	/**
	 * @var \stdClass
	 */
	protected $oResponsePackageData;

	/**
	 * @return string
	 * @deprecated
	 */
	public function getErrorMessage() {
		return $this->error_message;
	}

	/**
	 * @return bool
	 * @deprecated
	 */
	public function isSuccessful() {
		return (bool)$this->success;
	}

	/**
	 * @param bool $bAuthenticated
	 * @return $this
	 * @deprecated
	 */
	public function setAuthenticated( $bAuthenticated ) {
		$this->authenticated = $bAuthenticated ? 1 : 0;
		return $this;
	}

	/**
	 * @param string $sItem
	 * @param mixed  $mDefault
	 * @return mixed
	 * @deprecated
	 */
	protected function getResponseItem( $sItem, $mDefault = null ) {
		return $this->{$sItem};
	}

	/**
	 * @return array
	 * @deprecated
	 */
	public function getData() :array {
		return is_array( $this->data ) ? $this->data : [];
	}

	/**
	 * @param string $sChannel
	 * @return $this
	 * @deprecated
	 */
	public function setChannel( $sChannel ) {
		$this->channel = $sChannel;
		return $this;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getCode() {
		return $this->code;
	}

	/**
	 * @return bool
	 * @deprecated
	 */
	public function isDie() {
		return (bool)$this->die;
	}
}