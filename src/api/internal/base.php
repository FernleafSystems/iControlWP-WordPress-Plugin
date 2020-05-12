<?php

class ICWP_APP_Api_Internal_Base extends ICWP_APP_Foundation {

	/**
	 * @var ApiResponse
	 */
	protected $oActionResponse;

	/**
	 * @var RequestParameters
	 */
	protected $oRequestParams;

	public function preProcess() {
		if ( $this->isIgnoreUserAbort() ) {
			ignore_user_abort( true );
		}
		$this->initFtp();
	}

	protected function initFtp() {
		$aFtpCred = $this->getRequestParams()->getParam( 'ftpcred', null );
		if ( !empty( $aFtpCred ) && is_array( $aFtpCred ) ) {
			$aRequestToWpMappingFtp = [
				'hostname'        => 'ftp_host',
				'username'        => 'ftp_user',
				'password'        => 'ftp_pass',
				'public_key'      => 'ftp_public_key',
				'private_key'     => 'ftp_private_key',
				'connection_type' => 'ftp_protocol',
			];
			foreach ( $aRequestToWpMappingFtp as $sWpKey => $sRequestKey ) {
				$_POST[ $sWpKey ] = isset( $aFtpCred[ $sRequestKey ] ) ? $aFtpCred[ $sRequestKey ] : '';
			}

			$bUseFtp = false;
			if ( !empty( $aFtpCred[ 'ftp_user' ] ) ) {
				if ( !defined( 'FTP_USER' ) ) {
					$bUseFtp = true;
					define( 'FTP_USER', $aFtpCred[ 'ftp_user' ] );
				}
			}
			if ( !empty( $aFtpCred[ 'ftp_pass' ] ) ) {
				if ( !defined( 'FTP_PASS' ) ) {
					$bUseFtp = true;
					define( 'FTP_PASS', $aFtpCred[ 'ftp_pass' ] );
				}
			}

			if ( !empty( $_POST[ 'public_key' ] ) && !empty( $_POST[ 'private_key' ] ) && !defined( 'FS_METHOD' ) ) {
				define( 'FS_METHOD', 'ssh' );
			}
			elseif ( $bUseFtp ) {
				define( 'FS_METHOD', 'ftpext' );
			}
		}
	}

	public function process() {
	}

	/**
	 * @return ApiResponse
	 */
	public function getStandardResponse() {
		if ( is_null( $this->oActionResponse ) ) {
			$this->oActionResponse = new ApiResponse();
		}

		return $this->oActionResponse;
	}

	/**
	 * @param ApiResponse $oActionResponse
	 * @return $this
	 */
	public function setStandardResponse( $oActionResponse ) {
		$this->oActionResponse = $oActionResponse;
		return $this;
	}

	/**
	 * @param array  $aExecutionData
	 * @param string $sMessage
	 * @return ApiResponse
	 */
	protected function success( $aExecutionData = [], $sMessage = '' ) {
		return $this->getStandardResponse()
					->setSuccess( true )
					->setData( empty( $aExecutionData ) ? [ 'success' => 1 ] : $aExecutionData )
					->setMessage( sprintf( 'INTERNAL Package Execution SUCCEEDED with message: "%s".', $sMessage ) )
					->setCode( 0 );
	}

	/**
	 * @param string $sErrorMessage
	 * @param int    $nErrorCode
	 * @param mixed  $mErrorData
	 * @return ApiResponse
	 */
	protected function fail( $sErrorMessage = '', $nErrorCode = -1, $mErrorData = '' ) {
		return $this->getStandardResponse()
					->setFailed()
					->setErrorMessage( $sErrorMessage )
					->setCode( $nErrorCode )
					->setData( $mErrorData );
	}

	/**
	 * @return array
	 */
	protected function getActionParams() {
		return $this->getRequestParams()->getActionParams();
	}

	/**
	 * @param string     $sKey
	 * @param mixed|null $mDefault
	 * @return mixed|null
	 */
	protected function getActionParam( string $sKey, $mDefault = null ) {
		$aP = $this->getActionParams();
		return $aP[ $sKey ] ?? null;
	}

	/**
	 * @return RequestParameters
	 */
	public function getRequestParams() {
		return $this->oRequestParams;
	}

	/**
	 * @param RequestParameters $oRequestParams
	 * @return $this
	 */
	public function setRequestParams( $oRequestParams ) {
		$this->oRequestParams = $oRequestParams;

		return $this;
	}

	/**
	 * @return ICWP_APP_WpCollectInfo
	 */
	protected function getWpCollector() {
		return ICWP_APP_WpCollectInfo::GetInstance();
	}

	/**
	 * @return array
	 */
	protected function collectPlugins() {
		$oCollector = new ICWP_APP_Api_Internal_Collect_Plugins();
		return $oCollector->setRequestParams( $this->getRequestParams() )
						  ->collect();
	}

	/**
	 * @return array
	 */
	protected function collectThemes() {
		$oCollector = new ICWP_APP_Api_Internal_Collect_Themes();
		return $oCollector->setRequestParams( $this->getRequestParams() )
						  ->collect();
	}

	/**
	 * @return bool
	 */
	protected function isForceUpdateCheck() {
		$aActionParams = $this->getActionParams();
		return isset( $aActionParams[ 'force_update_check' ] ) ? (bool)$aActionParams[ 'force_update_check' ] : true;
	}

	/**
	 * @return bool
	 */
	protected function isIgnoreUserAbort() {
		$aActionParams = $this->getActionParams();
		return isset( $aActionParams[ 'ignore_user_abort' ] ) ? (bool)$aActionParams[ 'ignore_user_abort' ] : false;
	}
}