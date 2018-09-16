<?php

if ( class_exists( 'ICWP_APP_Api_Internal_Collect_Capabilities', false ) ) {
	return;
}

require_once( dirname( __FILE__ ).'/capabilities.php' );

class ICWP_APP_Api_Internal_Collect_Environment extends ICWP_APP_Api_Internal_Collect_Capabilities {

	/**
	 * @return array
	 */
	public function collect() {
		$oDp = $this->loadDataProcessor();
		if ( $oDp->suhosinFunctionExists( 'set_time_limit' ) ) {
			@set_time_limit( 15 );
		}

		$aAppsData = array();
		if ( $oDp->suhosinFunctionExists( 'exec' ) ) {
			$aAppVersionCmds = array(
				'mysql -V',
				'mysqldump -V',
				'mysqlimport -V',
				'unzip -v',
				'zip -v',
				'tar --version'
			);
			$aAppsData = $this->collectApplicationVersions( $aAppVersionCmds );
		}

		return array(
			'open_basedir'                 => ini_get( 'open_basedir' ),
			'safe_mode'                    => ini_get( 'safe_mode' ),
			'safe_mode_gid'                => ini_get( 'safe_mode_gid' ),
			'safe_mode_include_dir'        => ini_get( 'safe_mode_include_dir' ),
			'safe_mode_exec_dir'           => ini_get( 'safe_mode_exec_dir' ),
			'safe_mode_allowed_env_vars'   => ini_get( 'safe_mode_allowed_env_vars' ),
			'safe_mode_protected_env_vars' => ini_get( 'safe_mode_protected_env_vars' ),
			'can_exec'                     => $oDp->checkCanExec() ? 1 : 0,
			'can_timelimit'                => $oDp->checkCanTimeLimit() ? 1 : 0,
			'can_write'                    => $this->checkCanWrite() ? 1 : 0,
			'can_tar'                      => $aAppsData[ 'tar' ][ 'version-info' ] > 0 ? 1 : 0,
			'can_zip'                      => $aAppsData[ 'zip' ][ 'version-info' ] > 0 ? 1 : 0,
			'can_unzip'                    => $aAppsData[ 'unzip' ][ 'version-info' ] > 0 ? 1 : 0,
			'can_mysql'                    => $aAppsData[ 'mysql' ][ 'version-info' ] > 0 ? 1 : 0,
			'can_mysqldump'                => $aAppsData[ 'mysqldump' ][ 'version-info' ] > 0 ? 1 : 0,
			'can_mysqlimport'              => $aAppsData[ 'mysqlimport' ][ 'version-info' ] > 0 ? 1 : 0,
			'applications'                 => $aAppsData,
		);
	}

	/**
	 * @param array $aAppVersionCmds
	 * @return array
	 */
	protected function collectApplicationVersions( $aAppVersionCmds ) {
		$aApplications = array();

		foreach ( $aAppVersionCmds as $nIndex => $sVersionCmd ) {
			list( $sExecutable, $sVersionParam ) = explode( ' ', $sVersionCmd, 2 );
			@exec( $sVersionCmd, $aOutput, $nReturnVal );

			$aApplications[ $sExecutable ] = array(
				'exec'         => $sExecutable,
				'version-cmd'  => $sVersionCmd,
				'version-info' => $this->parseApplicationVersionOutput( $sExecutable, implode( "\n", $aOutput ) ),
				'found'        => $nReturnVal === 0,
			);
		}
		return $aApplications;
	}

	/**
	 * @param string $insExecutable
	 * @param string $insVersionOutput
	 * @return string
	 */
	protected function parseApplicationVersionOutput( $insExecutable, $insVersionOutput ) {
		$aRegExprs = array(
			'mysql'       => '/Distrib\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//mysql  Ver 14.14 Distrib 5.1.56, for pc-linux-gnu (i686) using readline 5.1
			'mysqlimport' => '/Distrib\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//mysqlimport  Ver 3.7 Distrib 5.1.41, for Win32 (ia32)
			'mysqldump'   => '/Distrib\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//mysqldump  Ver 10.13 Distrib 5.1.41, for Win32 (ia32)
			'zip'         => '/Zip\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//This is Zip 2.31 (March 8th 2005), by Info-ZIP.
			'unzip'       => '/UnZip\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i',
			//UnZip 5.52 of 28 February 2005, by Info-ZIP.  Maintained by C. Spieler.  Send
			'tar'         => '/tar\s+\(GNU\s+tar\)\s+([0-9]+\.[0-9]+(\.[0-9]+)?)/i'
			//tar (GNU tar) 1.15.1
		);

		if ( $insExecutable == 'php' ) {
			if ( preg_match( '/X-Pingback/i', $insVersionOutput ) ) {
				return '-2';
			}
		}
		if ( !preg_match( $aRegExprs[ $insExecutable ], $insVersionOutput, $aMatches ) ) {
			return '-3';
		}
		else {
			return $aMatches[ 1 ];
		}

		return '-1';
	}
}