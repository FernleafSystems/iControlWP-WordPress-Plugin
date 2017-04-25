<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Collect_Wordpress', false ) ):

	require_once( dirname( __FILE__ ).ICWP_DS.'base.php' );

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

			$oWp = $this->loadWpFunctionsProcessor();
			$aInfo = array(
				'is_multisite'			=> is_multisite()? 1: 0,
				'admin_url'				=> network_admin_url(),
				'core_update_available'	=> $oWp->getHasCoreUpdatesAvailable( $this->isForceUpdateCheck() )? 1 : 0,
				'wordpress_version'		=> $oWp->getWordPressVersion(),
				'wordpress_title'		=> get_bloginfo( 'name' ),
				'wordpress_tagline'		=> get_bloginfo( 'description' ),
				'config'				=> array(
					'table_prefix'			=> $this->loadDbProcessor()->getPrefix()
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
			foreach ( $aDefines as $sDefineKey ) {
				if ( defined( $sDefineKey ) ) {
					$aInfo['config'][strtolower( $sDefineKey )] = constant( $sDefineKey );
				}
			}

			return $aInfo;
		}
	}

endif;