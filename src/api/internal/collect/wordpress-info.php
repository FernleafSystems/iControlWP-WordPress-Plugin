<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Wordpress', false ) ):

	require_once( dirname( __FILE__ ).'/base.php' );

	class ICWP_APP_Api_Internal_Collect_Wordpress extends ICWP_APP_Api_Internal_Collect_Base {

		/**
		 * @return ApiResponse
		 */
		public function process() {
			$aData = array(
				'wordpress-info' => $this->collect(),
			);
			return $this->success( $aData );
		}

		/**
		 * @return array associative: ThemeStylesheet => ThemeData
		 */
		public function collect() {
			$oDp = $this->loadDataProcessor();
			$oWp = $this->loadWpFunctions();

			$aInfo = array(
				'is_multisite'          => is_multisite() ? 1 : 0,
				'type'                  => is_multisite() ? 'wpms' : 'wordpress',
				'admin_path'            => network_admin_url(),
				'admin_url'             => network_admin_url(), // TODO: DELETE
				'core_update_available' => $oWp->getHasCoreUpdatesAvailable( $this->isForceUpdateCheck() ) ? 1 : 0,
				'wordpress_version'     => $oWp->getWordPressVersion(),
				'wordpress_title'       => get_bloginfo( 'name' ),
				'wordpress_tagline'     => get_bloginfo( 'description' ),
				// moved from collect_sync
				'platform'              => $oDp->isWindows() ? 'Windows' : 'Linux',
				'windows'               => $oDp->isWindows() ? 1 : 0,
				'server_ip'             => $this->getServerAddress(),
				'php_version'           => $oDp->getPhpVersion(),
				'can_write'             => $this->getCollector_Capabilities()->canWrite() ? 1 : 0,
				'is_wpe'                => ( @getenv( 'IS_WPE' ) == '1' ) ? 1 : 0,
				'wordpress_url'         => $oWp->getHomeUrl(),
				'wordpress_wpurl'       => get_bloginfo( 'wpurl' ),
				'debug'                 => array(
					'url_rewritten'   => $oDp->isUrlRewritten() ? 1 : 0,
					'database_server' => isset( $_ENV[ 'DATABASE_SERVER' ] ) ? $_ENV[ 'DATABASE_SERVER' ] : '-1',
					'ds'              => DIRECTORY_SEPARATOR,
				)
			);

			$aDefines = array(
				'FS_METHOD',
				'DISALLOW_FILE_EDIT',
				'FORCE_SSL_LOGIN',
				'FORCE_SSL_ADMIN',
				'DB_PASSWORD',
				'WP_ALLOW_MULTISITE',
				'MULTISITE',
				'DB_HOST',
				'DB_NAME',
				'DB_USER',
				'DB_PASSWORD',
				'DB_CHARSET',
				'DB_COLLATE',
			);

			$aWpConfig = array(
				'table_prefix' => $this->loadDbProcessor()->getPrefix()
			);
			foreach ( $aDefines as $sDefineKey ) {
				if ( defined( $sDefineKey ) ) {
					$aWpConfig[ strtolower( $sDefineKey ) ] = constant( $sDefineKey );
				}
			}

			$aInfo[ 'config' ] = $aWpConfig; // TODO: delete; backwards compat
			$aInfo[ 'wordpress_config' ] = $aWpConfig;

			return $aInfo;
		}

		/**
		 * Attempts to find a valid server IP address whether it's Windows or *nix.
		 * @return string
		 */
		protected function getServerAddress() {
			if ( $this->loadDataProcessor()->isWindows() ) {
				if ( !isset( $_SERVER[ 'SERVER_ADDR' ] ) || empty( $_SERVER[ 'SERVER_ADDR' ] ) ) {
					if ( isset( $_SERVER[ 'LOCAL_ADDR' ] ) && !empty( $_SERVER[ 'LOCAL_ADDR' ] ) ) {
						$sAddress = $_SERVER[ 'LOCAL_ADDR' ];
					}
					else {
						$sAddress = '0.0.0.0';
					}
				}
				else {
					$sAddress = $_SERVER[ 'SERVER_ADDR' ];
				}
			}
			else {
				$sAddress = $_SERVER[ 'SERVER_ADDR' ];
			}

			if ( $this->isPrivateIp( $sAddress ) && function_exists( 'gethostbyname' )
				 && isset( $_SERVER[ 'SERVER_NAME' ] ) && !empty( $_SERVER[ 'SERVER_NAME' ] )
			) {
				$sAddress = gethostbyname( $_SERVER[ 'SERVER_NAME' ] );
			}

			return $sAddress;
		}

		/**
		 * @param string $sIp
		 * @return bool
		 */
		private function isPrivateIp( $sIp ) {
			return !filter_var( $sIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		}
	}

endif;