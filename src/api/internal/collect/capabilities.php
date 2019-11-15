<?php

class ICWP_APP_Api_Internal_Collect_Capabilities extends ICWP_APP_Api_Internal_Collect_Base {

	/**
	 * @var bool
	 */
	private $bCanWrite;

	/**
	 * @return ApiResponse
	 */
	public function process() {
		return $this->success( [ 'capabilities' => $this->collect() ] );
	}

	/**
	 * @return array
	 */
	public function collect() {
		$oDp = $this->loadDP();
		$bCanExtensionLoaded = function_exists( 'extension_loaded' ) && is_callable( 'extension_loaded' );

		$aData = [
			'php_version'                => $oDp->getPhpVersion(), //TODO DELETE
			'version_php'                => $oDp->getPhpVersion(),
			'is_force_ssl_admin'         => ( function_exists( 'force_ssl_admin' ) && force_ssl_admin() ) ? 1 : 0,
			'can_handshake'              => $this->isHandshakeEnabled() ? 1 : 0,
			'can_handshake_openssl'      => $this->loadEncryptProcessor()->getSupportsOpenSslSign() ? 1 : 0,
			'can_wordpress_write'        => $this->checkCanWordpressWrite( $sWriteToDiskNotice ) ? 1 : 0, //TODO: DELETE
			'can_wordpress_write_notice' => $sWriteToDiskNotice,
			'ext_pdo'                    => class_exists( 'PDO' ) || ( $bCanExtensionLoaded && extension_loaded( 'pdo' ) ),
			'ext_mysqli'                 => ( $bCanExtensionLoaded && extension_loaded( 'mysqli' ) ) ? 1 : 0,
		];

		return $aData;
	}

	/**
	 * @return bool
	 */
	protected function checkCanWrite() {
		$oFS = $this->loadFS();

		$sWorkingTestDir = dirname( __FILE__ ).'/icwp_test/';
		$sWorkingTestFile = $sWorkingTestDir.'test_write';
		$sTestContent = '#FINDME-'.uniqid();

		$bGoodSoFar = true;
		$outsMessage = '';

		if ( !$oFS->mkdir( $sWorkingTestDir ) || !$oFS->isDir( $sWorkingTestDir ) ) {
			$outsMessage = sprintf( 'Failed to create directory: %s', $sWorkingTestDir );
			$bGoodSoFar = false;
		}
		if ( $bGoodSoFar && !is_writable( $sWorkingTestDir ) ) {
			$outsMessage = sprintf( 'The test directory is not writable: %s', $sWorkingTestDir );
			$bGoodSoFar = false;
		}
		if ( $bGoodSoFar && !$oFS->touch( $sWorkingTestFile ) ) {
			$outsMessage = sprintf( 'Failed to touch "%s"', $sWorkingTestFile );
			$bGoodSoFar = false;
		}
		if ( $bGoodSoFar && !file_put_contents( $sWorkingTestFile, $sTestContent ) ) {
			$outsMessage = sprintf( 'Failed to write content "%s" to "%s"', $sWorkingTestFile, $sTestContent );
			$bGoodSoFar = false;
		}
		if ( $bGoodSoFar && !@is_file( $sWorkingTestFile ) ) {
			$outsMessage = sprintf( 'Failed to find file "%s"', $sWorkingTestFile );
			$bGoodSoFar = false;
		}
		$sContents = $oFS->getFileContent( $sWorkingTestFile );
		if ( $bGoodSoFar && ( $sContents != $sTestContent ) ) {
			$outsMessage = sprintf( 'The content "%s" does not match what we wrote "%s"', $sContents, $sTestContent );
			$bGoodSoFar = false;
		}

		if ( !$bGoodSoFar ) {
			$this->getStandardResponse()
				 ->setErrorMessage( $outsMessage );

			return false;
		}

		$oFS->deleteDir( $sWorkingTestDir );

		return true;
	}

	/**
	 * @param string &$outsMessage
	 * @return boolean
	 */
	protected function checkCanWordpressWrite( &$outsMessage = '' ) {
		$sUrl = '';
		$sUrl = wp_nonce_url( $sUrl, '' );

		ob_start();
		$aCredentials = request_filesystem_credentials( $sUrl, '', false, false, null );
		ob_end_clean();

		if ( $aCredentials === false ) {
			$outsMessage = 'Could not obtain filesystem credentials';
			return false;
		}

		if ( !WP_Filesystem( $aCredentials ) ) {
			global $wp_filesystem;

			$oWpError = null;
			if ( is_object( $wp_filesystem ) && $wp_filesystem->errors->get_error_code() ) {
				$oWpError = $wp_filesystem->errors;
				/** @var WP_Error $oWpError */
			}
			$outsMessage = sprintf( 'Cannot connect to filesystem. Error: "%s"',
				is_wp_error( $oWpError ) ? $oWpError->get_error_message() : ''
			);

			return false;
		}

		$outsMessage = 'WordPress disk write successful.';
		return true;
	}

	/**
	 * @return boolean
	 */
	protected function isHandshakeEnabled() {
		if ( method_exists( 'ICWP_Plugin', 'GetHandshakingEnabled' ) ) {
			return ICWP_Plugin::GetHandshakingEnabled();
		}
		return apply_filters( 'icwp-app-CanHandshake', false );
	}

	/**
	 * @return bool
	 */
	public function canWrite() {
		if ( !isset( $this->bCanWrite ) ) {
			$this->bCanWrite = $this->checkCanWordpressWrite();
		}
		return $this->bCanWrite;
	}
}