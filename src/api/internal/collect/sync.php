<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Capabilities', false ) ):

	require_once( dirname( __FILE__ ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Collect_Sync extends ICWP_APP_Api_Internal_Collect_Base {
		/**
		 * @return ApiResponse
		 */
		public function process() {
			$oDp = $this->loadDataProcessor();

			$bCanPHPWrite = $this->checkCanWrite();

			$fCanExtensionLoaded = function_exists('extension_loaded') && is_callable('extension_loaded');
			$aData = array(
				'platform'				=> $oDp->isWindows()? 'Windows': 'Linux',
				'php_version'			=> $oDp->getPhpVersion(),
				'server_ip'				=> $oDp->getVisitorIpAddress(),
				'url_rewritten'			=> $oDp->isUrlRewritten() ? 1: 0,
				'database_server'		=> $oDp->FetchEnv( 'DATABASE_SERVER', '-1' ),
				'is_wpe'				=> @getenv( 'IS_WPE' ) == '1',
				'capabilities'			=> array(
					'open_basedir'					=> ini_get( 'open_basedir' ),
					'safe_mode'						=> ini_get( 'safe_mode' ),
					'safe_mode_gid'					=> ini_get( 'safe_mode_gid' ),
					'safe_mode_include_dir'			=> ini_get( 'safe_mode_include_dir' ),
					'safe_mode_exec_dir'			=> ini_get( 'safe_mode_exec_dir' ),
					'safe_mode_allowed_env_vars'	=> ini_get( 'safe_mode_allowed_env_vars' ),
					'safe_mode_protected_env_vars'	=> ini_get( 'safe_mode_protected_env_vars' ),
					'can_timelimit'					=> $oDp->checkCanTimeLimit() ? 1: 0,
					'can_write'						=> $bCanPHPWrite? 1: 0,
					'can_wordpress_write'			=> $this->checkCanWordpressWrite( $sWriteToDiskNotice )? 1: 0,
					'can_wordpress_write_notice'	=> $sWriteToDiskNotice,
					'can_exec'						=> $oDp->checkCanExec()? 1: 0,
					'ext_pdo'						=> class_exists( 'PDO' ) || ( $fCanExtensionLoaded && extension_loaded( 'pdo' ) ),
					'ext_mysqli'					=> $fCanExtensionLoaded && extension_loaded( 'mysqli' ),
					'is_force_ssl_admin'			=> function_exists( 'force_ssl_admin' ) && force_ssl_admin(),
					'handshake_enabled'				=> $this->isHandshakeEnabled() ? 1 : 0,
				),
				'debug-info' => array(
					// get_bloginfo( 'url' ) == home_url() but we call it first here because our "getHomeUrl()" removes filters
					'wordpress_url'					=> get_bloginfo( 'url' )
				),
				'paths'					=> $this->getWpPaths(),
				'ds'					=> DIRECTORY_SEPARATOR,
				'wordpress-info'		=> $this->collectSettings(),
				'wordpress-db'			=> $this->getDbSettings(),
				'wordpress-extras'		=> array( ),
				'debug' => array()

				//,'wordpress-filters'		=> global $wp_filter; $wp_filter
			);

			if ( class_exists( 'DirectoryIterator', false ) ) {
				$this->cleanRollbackData();
				$this->cleanRollbackDir();
			}

			return $this->success( $aData );
		}

		/**
		 * @return array
		 */
		public function collectSettings() {
			$aInfo = array(
				'is_multisite'			=> is_multisite()? 1: 0,
				'wordpress_title'		=> get_bloginfo( 'name' ),
				'wordpress_tagline'		=> get_bloginfo( 'description' ),
				'wordpress_version'		=> $this->loadWpFunctionsProcessor()->getWordpressVersion(),
				'config'				=> array(
					'table_prefix'			=> ''
				)
			);
			return $aInfo;
		}

		/**
		 * @return bool
		 */
		protected function checkCanWrite() {
			$oFS = $this->loadFileSystemProcessor();

			$sWorkingTestDir = dirname( __FILE__ ).DIRECTORY_SEPARATOR.'icwp_test'.DIRECTORY_SEPARATOR;
			$sWorkingTestFile = $sWorkingTestDir.'test_write';
			$sTestContent = '#FINDME-'.uniqid();

			$bGoodSoFar = true;
			$outsMessage = '';

			if ( ! $oFS->mkdir( $sWorkingTestDir ) || !$oFS->isDir( $sWorkingTestDir ) ) {
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
					$oWpError = $wp_filesystem->errors; /** @var WP_Error $oWpError */
				}
				$outsMessage = sprintf( 'Cannot connect to filesystem. Error: "%s"',
					is_wp_error( $oWpError )? $oWpError->get_error_message(): ''
				);
				return false;
			}
			return true;
		}

		/**
		 * @return array
		 */
		protected function getDbSettings() {
			$aSettings = array(
				'table_prefix' => $this->loadDbProcessor()->getPrefix()
			);
			$aDefines = array(
				'DB_HOST',
				'DB_NAME',
				'DB_USER',
				'DB_PASSWORD',
				'DB_CHARSET',
				'DB_COLLATE'
			);
			foreach ( $aDefines as $sDefineKey ) {
				if ( defined( $sDefineKey ) ) {
					$aSettings[ strtolower( $sDefineKey ) ] = constant( $sDefineKey );
				}
			}
			return $aSettings;
		}

		/**
		 * @return array
		 */
		protected function getWpPaths() {

			$oWp = $this->loadWpFunctionsProcessor();
			$sHomeUrl = $oWp->getHomeUrl();
			$sSiteUrl = $oWp->getSiteUrl();

			// trust the URL to determine the split
			$sHomeDir = preg_replace( '|https?://[^/]+|i', '', trim( $sHomeUrl, '/' ) . '/' );
			$sSiteDir = preg_replace( '|https?://[^/]+|i', '', trim( $sSiteUrl, '/' ) . '/' );
			$bIsSplitPath = trim( $sHomeDir, '/' ) !== trim( $sSiteDir, '/' );

			$sServer_ScriptFilename = isset( $_SERVER['SCRIPT_FILENAME'] ) ? $_SERVER['SCRIPT_FILENAME'] : '';

			// we cannot trust paths, as a whole world of things can happen to manipulate them
			if ( !empty( $sServer_ScriptFilename ) && !preg_match( '/wp-content|plugins/i', $sServer_ScriptFilename ) ) {
				$sRoot = rtrim( dirname( $sServer_ScriptFilename ), DIRECTORY_SEPARATOR );
				$sDiff = trim( str_replace( $sHomeDir, '', $sSiteDir ), '/' );

				// It's running through the WP Admin so we chop it off.
				if ( strpos( $sServer_ScriptFilename, 'wp-admin' ) !== false ) {
					$sRoot = rtrim( dirname( $sRoot ), DIRECTORY_SEPARATOR );
				}

				// ensure that when we add the diff to the homedir, that it exists.
				// if it doesn't exist and the last section of the home dir is the same as the diff
				// then we remove the diff from the home dir
				$sAbsHomeDir = $sRoot;
				if ( $sDiff && !is_dir( $sAbsHomeDir.DIRECTORY_SEPARATOR.$sDiff ) && end( explode( DIRECTORY_SEPARATOR, $sAbsHomeDir ) ) == $sDiff ) {
					// take the last part off the home dir
					$sAbsHomeDir = implode( DIRECTORY_SEPARATOR, array_slice( explode( DIRECTORY_SEPARATOR, $sAbsHomeDir ), 0, -1 ) );
				}

				// if the last section of the home dir, is the same as the diff, then we will assume this is not
				// to be expected.

				if ( $bIsSplitPath ) {
					$sAbsSiteDir = rtrim( $sAbsHomeDir.DIRECTORY_SEPARATOR.$sDiff, DIRECTORY_SEPARATOR );
				}
				else {
					$sAbsSiteDir = $sAbsHomeDir;
				}
			}
			else {
				$sRoot = rtrim( $_SERVER['DOCUMENT_ROOT'], '/' );
				$sAbsHomeDir = rtrim( rtrim( $sRoot, '/' ).'/'.trim( $sHomeDir, '/' ), '/' );
				$sAbsSiteDir = rtrim( rtrim( $sRoot, '/' ).'/'.trim( $sSiteDir, '/' ), '/' );
			}

			$sWpConfig = $this->findWpConfig();
			$bRelocatedWpConfig = $sWpConfig !== false && $oWp->isWpConfigRelocated( $sWpConfig, ABSPATH );
			$sUploadsDir = defined( 'UPLOADS' )? untrailingslashit( UPLOADS ): untrailingslashit( WP_CONTENT_DIR ).'/uploads';

			return array(
				'wordpress_url'                => $sHomeUrl, // get_bloginfo( 'url' ),
				'wordpress_wpurl'              => get_bloginfo( 'wpurl' ),
				'wordpress_home_url'           => $sHomeUrl, //network_home_url()
				'wordpress_site_url'           => network_site_url(),
				'wordpress_admin_url'          => network_admin_url(),
				'wordpress_includes_url'       => includes_url(),
				'wordpress_content_url'        => content_url(), // WP_CONTENT_URL
				'wordpress_plugin_url'         => plugins_url(), // WP_PLUGIN_URL

				'wordpress_home_dir'           => $sHomeDir,
				'wordpress_site_dir'           => $sSiteDir,
				'wordpress_abs_home_dir'       => $sAbsHomeDir,
				//$bIsSplitPath? rtrim( $sRoot.'/'.trim( $sHomeDir, '/' ), '/' ): rtrim( $sRoot, '/' ),
				'wordpress_abs_home_dir_r'     => rtrim( realpath( $sAbsHomeDir ), '/' ),
				'wordpress_abs_site_dir'       => $sAbsSiteDir,
				'wordpress_abs_site_dir_r'     => rtrim( realpath( $sAbsSiteDir ), '/' ),
				'wordpress_abspath'            => rtrim( ABSPATH, '/' ),
				'wordpress_abspath_r'          => rtrim( realpath( ABSPATH ), '/' ),
				'wordpress_includes_dir'       => rtrim( ABSPATH . WPINC, '/' ),
				'wordpress_content_dir'        => rtrim( WP_CONTENT_DIR, '/' ),
				'wordpress_plugin_dir'         => rtrim( WP_PLUGIN_DIR, '/' ),
				'wordpress_upload_dir'         => rtrim( $sUploadsDir, '/' ),
				'wordpress_worpit_plugin_dir'  => rtrim( $this->getDriverRootDir(), '/' ),
				'wordpress_wpconfig'           => $sWpConfig,
				'wordpress_wpconfig_relocated' => $bRelocatedWpConfig ? 1 : 0,
				'php_self'                     => isset( $_SERVER[ 'PHP_SELF' ] ) ? $_SERVER[ 'PHP_SELF' ] : '-1',
				'document_root'                => isset( $_SERVER[ 'DOCUMENT_ROOT' ] ) ? $_SERVER[ 'DOCUMENT_ROOT' ] : '-1',
				'script_filename'              => isset( $_SERVER[ 'SCRIPT_FILENAME' ] ) ? $_SERVER[ 'SCRIPT_FILENAME' ] : '-1',
				'path_translated'              => isset( $_SERVER[ 'PATH_TRANSLATED' ] ) ? $_SERVER[ 'PATH_TRANSLATED' ] : '-1'
			);
		}

		/**
		 * @return string
		 */
		protected function getDriverRootDir() {
			if ( class_exists( 'ICWP_Plugin' ) && method_exists( 'ICWP_Plugin', 'getController' ) ) {
				return ICWP_Plugin::getController()->getRootDir();
			}
			return '';
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
		 * @param string $sSearchLocation
		 * @param bool $bIncludeBackwardsLookup
		 * @return string|bool
		 */
		protected function findWpConfig( $sSearchLocation = null, $bIncludeBackwardsLookup = true ) {
			if ( is_null( $sSearchLocation ) ) {
				if ( defined( 'ABSPATH' ) ) {
					if ( is_file( rtrim( ABSPATH, '/' ).'/wp-config.php' ) ) {
						return rtrim( ABSPATH, '/' ).'/wp-config.php';
					}
					if ( $bIncludeBackwardsLookup && is_file( rtrim( ABSPATH, '/' ).'/../wp-config.php' ) ) {
						return realpath( rtrim( ABSPATH, '/' ).'/../wp-config.php' );
					}
				}
				if ( defined( 'REQUEST_ABS_HOME_DIR' ) ) {
					if ( is_file( rtrim( REQUEST_ABS_HOME_DIR, '/' ).'/wp-config.php' ) ) {
						return rtrim( REQUEST_ABS_HOME_DIR, '/' ).'/wp-config.php';
					}
					if ( $bIncludeBackwardsLookup && is_file( rtrim( REQUEST_ABS_HOME_DIR, '/' ).'/../wp-config.php' ) ) {
						return realpath( rtrim( REQUEST_ABS_HOME_DIR, '/' ).'/../wp-config.php' );
					}
				}
				if ( defined( 'REQUEST_ABS_SITE_DIR' ) ) {
					if ( is_file( rtrim( REQUEST_ABS_SITE_DIR, '/' ).'/wp-config.php' ) ) {
						return rtrim( REQUEST_ABS_SITE_DIR, '/' ).'/wp-config.php';
					}
					if ( $bIncludeBackwardsLookup && is_file( rtrim( REQUEST_ABS_SITE_DIR, '/' ).'/../wp-config.php' ) ) {
						return realpath( rtrim( REQUEST_ABS_SITE_DIR, '/' ).'/../wp-config.php' );
					}
				}

				if ( isset( $_SERVER['DOCUMENT_ROOT'] ) ) {
					if ( is_file( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ).'/wp-config.php' ) ) {
						return rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ).'/wp-config.php';
					}
					if ( $bIncludeBackwardsLookup && is_file( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ).'/../wp-config.php' ) ) {
						return realpath( rtrim( $_SERVER['DOCUMENT_ROOT'], '/' ).'/../wp-config.php' );
					}
				}
			}
			else {
				if ( is_file( rtrim( $sSearchLocation, '/' ).'/wp-config.php' ) ) {
					return rtrim( $sSearchLocation, '/' ).'/wp-config.php';
				}
				if ( $bIncludeBackwardsLookup && is_file( rtrim( $sSearchLocation, '/' ).'/../wp-config.php' ) ) {
					return realpath( rtrim( $sSearchLocation, '/' ).'/../wp-config.php' );
				}
			}
			return false;
		}

		/**
		 * @return bool
		 */
		protected function cleanRollbackData() {

			$nBoundary = time() - WEEK_IN_SECONDS;
			$oFs = $this->loadFileSystemProcessor();

			$aContexts = array( 'plugins', 'themes' );
			foreach( $aContexts as $sContext )  {
				$sWorkingDir = path_join( $this->getRollbackBaseDir(), $sContext );
				if ( !is_dir( $sWorkingDir ) ) {
					continue;
				}
				try {
					$oDirIt = new DirectoryIterator( $sWorkingDir );
					foreach ( $oDirIt as $oFileItem ) {
						if ( $oFileItem->isDir() && !$oFileItem->isDot() ) {
							if ( $oFileItem->getMTime() < $nBoundary ) {
								$oFs->deleteDir( $oFileItem->getPathname() );
							}
						}
					}
				}
				catch( Exception $oE ) { //  UnexpectedValueException, RuntimeException, Exception
					continue;
				}
			}
			return true;
		}

		/**
		 */
		protected function cleanRollbackDir() {
			$oFs = $this->loadFileSystemProcessor();

			try {
				$oDirIt = new DirectoryIterator( $this->getRollbackBaseDir() );
				foreach ( $oDirIt as $oFileItem ) {
					if ( !$oFileItem->isDot() ) {

						if ( !$oFileItem->isDir() ) {
							$oFs->deleteFile( $oFileItem->getPathname() );
						}
						else if ( !in_array( $oFileItem->getFilename(), array( 'plugins', 'themes' ) ) ) {
							$oFs->deleteDir( $oFileItem->getPathname() );
						}
					}
				}
			}
			catch( Exception $oE ) {
				//  UnexpectedValueException, RuntimeException, Exception
			}
		}

		/**
		 * @return string
		 */
		protected function getRollbackBaseDir() {
			return path_join( WP_CONTENT_DIR, 'icwp'.ICWP_DS.'rollback'.ICWP_DS );
		}
	}

endif;