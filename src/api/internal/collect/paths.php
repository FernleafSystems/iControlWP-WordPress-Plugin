<?php

class ICWP_APP_Api_Internal_Collect_Paths extends ICWP_APP_Api_Internal_Collect_Base {

	/**
	 * @return ApiResponse
	 */
	public function process() {
		return $this->success( [ 'paths' => $this->collect() ] );
	}

	/**
	 * @return array
	 */
	public function collect() {
		return $this->getWpPaths();
	}

	/**
	 * @return array
	 */
	protected function getDbSettings() {
		$aDefines = [
			'DB_HOST',
			'DB_NAME',
			'DB_USER',
			'DB_PASSWORD',
			'DB_CHARSET',
			'DB_COLLATE'
		];

		$aSettings = [
			'table_prefix' => $this->loadDbProcessor()->getPrefix()
		];
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

		$oWp = $this->loadWP();
		$sHomeUrl = $oWp->getHomeUrl();
		$sSiteUrl = $oWp->getSiteUrl();

		// trust the URL to determine the split
		$sHomeDir = preg_replace( '|https?://[^/]+|i', '', trim( $sHomeUrl, '/' ).'/' );
		$sSiteDir = preg_replace( '|https?://[^/]+|i', '', trim( $sSiteUrl, '/' ).'/' );
		$bIsSplitPath = trim( $sHomeDir, '/' ) !== trim( $sSiteDir, '/' );

		$sServer_ScriptFilename = isset( $_SERVER[ 'SCRIPT_FILENAME' ] ) ? $_SERVER[ 'SCRIPT_FILENAME' ] : '';

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
			$aAbsHomeParts = explode( DIRECTORY_SEPARATOR, $sAbsHomeDir );
			if ( $sDiff && !is_dir( path_join( $sAbsHomeDir, $sDiff ) ) && end( $aAbsHomeParts ) == $sDiff ) {
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
			$sRoot = rtrim( $_SERVER[ 'DOCUMENT_ROOT' ], '/' );
			$sAbsHomeDir = rtrim( rtrim( $sRoot, '/' ).'/'.trim( $sHomeDir, '/' ), '/' );
			$sAbsSiteDir = rtrim( rtrim( $sRoot, '/' ).'/'.trim( $sSiteDir, '/' ), '/' );
		}

		$sWpConfig = $this->findWpConfig();
		$bRelocatedWpConfig = $sWpConfig !== false && $oWp->isWpConfigRelocated( $sWpConfig, ABSPATH );
		$sUploadsDir = defined( 'UPLOADS' ) ? untrailingslashit( UPLOADS ) : untrailingslashit( WP_CONTENT_DIR ).'/uploads';

		return [
			'wordpress_url'          => $sHomeUrl, // get_bloginfo( 'url' ),
			'wordpress_wpurl'        => get_bloginfo( 'wpurl' ),
			'wordpress_home_url'     => $sHomeUrl, //network_home_url()
			'wordpress_site_url'     => network_site_url(),
			'wordpress_admin_url'    => network_admin_url(),
			'admin_url'              => network_admin_url(),
			'wordpress_includes_url' => includes_url(),
			'wordpress_content_url'  => content_url(), // WP_CONTENT_URL
			'wordpress_plugin_url'   => plugins_url(), // WP_PLUGIN_URL

			'wordpress_home_dir'           => $sHomeDir,
			'wordpress_site_dir'           => $sSiteDir,
			'wordpress_abs_home_dir'       => $sAbsHomeDir,
			//$bIsSplitPath? rtrim( $sRoot.'/'.trim( $sHomeDir, '/' ), '/' ): rtrim( $sRoot, '/' ),
			'wordpress_abs_home_dir_r'     => rtrim( realpath( $sAbsHomeDir ), '/' ),
			'wordpress_abs_site_dir'       => $sAbsSiteDir,
			'wordpress_abs_site_dir_r'     => rtrim( realpath( $sAbsSiteDir ), '/' ),
			'wordpress_abspath'            => rtrim( ABSPATH, '/' ),
			'wordpress_abspath_r'          => rtrim( realpath( ABSPATH ), '/' ),
			'wordpress_includes_dir'       => rtrim( ABSPATH.WPINC, '/' ),
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
		];
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
	 * @param string $sSearchLocation
	 * @param bool   $bIncludeBackwardsLookup
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

			if ( isset( $_SERVER[ 'DOCUMENT_ROOT' ] ) ) {
				if ( is_file( rtrim( $_SERVER[ 'DOCUMENT_ROOT' ], '/' ).'/wp-config.php' ) ) {
					return rtrim( $_SERVER[ 'DOCUMENT_ROOT' ], '/' ).'/wp-config.php';
				}
				if ( $bIncludeBackwardsLookup && is_file( rtrim( $_SERVER[ 'DOCUMENT_ROOT' ], '/' ).'/../wp-config.php' ) ) {
					return realpath( rtrim( $_SERVER[ 'DOCUMENT_ROOT' ], '/' ).'/../wp-config.php' );
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
}