<?php

if ( class_exists( 'ICWP_APP_Api_Internal_Collect_Capabilities', false ) ) {
	return;
}

require_once( dirname( __FILE__ ).'/base.php' );

class ICWP_APP_Api_Internal_Collect_Capabilities extends ICWP_APP_Api_Internal_Collect_Base {

	/**
	 * @var bool
	 */
	private $bCanWrite;

	/**
	 * @var bool
	 */
	private $bDoFullCapabilitiesTest;

	/**
	 * @return ApiResponse
	 */
	public function process() {
		$aData = array(
			'capabilities' => $this->collect()
		);
		return $this->success( $aData );
	}

	/**
	 * @return array
	 */
	public function collect() {
		$oDp = $this->loadDataProcessor();
		$bCanExtensionLoaded = function_exists( 'extension_loaded' ) && is_callable( 'extension_loaded' );

		$bCanWpWrite = $this->checkCanWordpressWrite( $sWriteToDiskNotice ) ? 1 : 0;
		$aData = array(
			'php_version'                => $oDp->getPhpVersion(), //TODO DELETE
			'version_php'                => $oDp->getPhpVersion(),
			'is_force_ssl_admin'         => ( function_exists( 'force_ssl_admin' ) && force_ssl_admin() ) ? 1 : 0,
			'can_handshake'              => $this->isHandshakeEnabled() ? 1 : 0,
			'can_handshake_openssl'      => $this->loadEncryptProcessor()->getSupportsOpenSslSign() ? 1 : 0,
			'can_wordpress_write'        => $bCanWpWrite, //TODO: DELETE
			'can_wordpress_write_notice' => $sWriteToDiskNotice,
		);

		if ( $this->isDoFullCapabilitiesTest() ) {

			$aData = array_merge(
				$aData,
				array(
					'open_basedir'                 => ini_get( 'open_basedir' ),
					'safe_mode'                    => ini_get( 'safe_mode' ),
					'safe_mode_gid'                => ini_get( 'safe_mode_gid' ),
					'safe_mode_include_dir'        => ini_get( 'safe_mode_include_dir' ),
					'safe_mode_exec_dir'           => ini_get( 'safe_mode_exec_dir' ),
					'safe_mode_allowed_env_vars'   => ini_get( 'safe_mode_allowed_env_vars' ),
					'safe_mode_protected_env_vars' => ini_get( 'safe_mode_protected_env_vars' ),
					'can_timelimit'                => $oDp->checkCanTimeLimit() ? 1 : 0,
					'can_write'                    => $this->checkCanWrite() ? 1 : 0,
//					'can_exec'                     => $oDp->checkCanExec() ? 1 : 0,
					'ext_pdo'                      => class_exists( 'PDO' ) || ( $bCanExtensionLoaded && extension_loaded( 'pdo' ) ),
					'ext_mysqli'                   => ( $bCanExtensionLoaded && extension_loaded( 'mysqli' ) ) ? 1 : 0,
				)
			);
		}

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
		if ( class_exists( 'ICWP_Plugin', false ) && method_exists( 'ICWP_Plugin', 'GetHandshakingEnabled' ) ) {
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

	/**
	 * @return bool
	 */
	public function isDoFullCapabilitiesTest() {
		return (bool)$this->bDoFullCapabilitiesTest;
	}

	/**
	 * @param bool $bDoFullCapabilitiesTest
	 * @return $this
	 */
	public function setDoFullCapabilitiesTest( $bDoFullCapabilitiesTest ) {
		$this->bDoFullCapabilitiesTest = $bDoFullCapabilitiesTest;
		return $this;
	}
}