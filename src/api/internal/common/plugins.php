<?php

if ( !class_exists( 'ICWP_APP_Api_Internal_Common_Plugins', false ) ):

	require_once( dirname( dirname( __FILE__ ) ).ICWP_DS.'base.php' );

	class ICWP_APP_Api_Internal_Common_Plugins extends ICWP_APP_Api_Internal_Base {

		/**
		 * @param string $sFile
		 * @param string $sContext
		 * @return boolean
		 */
		public function prepRollbackData( $sFile, $sContext = 'plugins' ) {
			$sPluginDirName = dirname( $sFile );
			$sPluginDirPath = path_join( WP_PLUGIN_DIR, $sPluginDirName );

			$sDestinationPath = path_join( $this->getRollbackBaseDir(), $sContext.ICWP_DS.$sPluginDirName );
			if ( is_dir( $sDestinationPath ) ) {
				/** @var WP_Filesystem_Base $wp_filesystem */
				global $wp_filesystem;
				$wp_filesystem->rmdir( $sDestinationPath, true );
			}
			wp_mkdir_p( $sDestinationPath );
			return copy_dir( $sPluginDirPath, $sDestinationPath );
		}

		/**
		 * @return string
		 */
		protected function getRollbackBaseDir() {
			return path_join( WP_CONTENT_DIR, 'icwp'.ICWP_DS.'rollback'.ICWP_DS );
		}
	}

endif;